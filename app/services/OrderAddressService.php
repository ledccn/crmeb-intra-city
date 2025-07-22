<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\order\StoreOrder;
use app\model\order\StoreOrderStatus;
use app\model\user\UserAddress;
use app\services\order\StoreOrderServices;
use ErrorException;
use InvalidArgumentException;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\model\EbStoreOrderChangeAddress;
use Ledc\CrmebIntraCity\parameters\HasStoreOrder;
use Ledc\CrmebIntraCity\ServiceTransEnums;
use Ledc\CrmebIntraCity\WechatIntraCityHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * 订单地址服务（变更收货信息）
 */
class OrderAddressService
{
    use HasStoreOrder;

    /**
     * 构造函数
     * @param StoreOrder $storeOrder
     */
    final public function __construct(StoreOrder $storeOrder)
    {
        $this->setStoreOrder($storeOrder);
    }

    /**
     * 审核变更地址或电话
     * @param bool $state 审核状态：true 通过，false 拒绝
     * @param string $reason 审核原因
     * @param bool $force 是否强制操作（审核状态true时，取消订单可能产生费用，需传true）
     * @param bool $reprint 审核通过、是否重新打印
     * @return bool
     */
    final public function auditChangeAddress(bool $state, string $reason, bool $force, bool $reprint = false): bool
    {
        $storeOrder = $this->getStoreOrder();
        $change_user_address_id = $storeOrder->change_user_address_id;
        if (!$change_user_address_id) {
            throw new ValidateException('用户已取消申请，无需审核');
        }
        $orderChangeAddress = EbStoreOrderChangeAddress::findOrEmpty($change_user_address_id);
        if ($orderChangeAddress->isEmpty()) {
            $storeOrder->change_user_address_id = 0;
            $storeOrder->save();
            throw new ValidateException('订单变更信息不存在');
        }

        if ($state) {
            if (!$orderChangeAddress->isPaid()) {
                throw new ValidateException('请等待用户支付成功后再审核');
            }
            if ($orderChangeAddress->isRefunded()) {
                throw new ValidateException('用户已申请退款，无需审核');
            }
        }

        // 记录订单变更日志
        StoreOrderStatus::create([
            'oid' => $storeOrder->id,
            'change_type' => OrderChangeTypeEnums::CHANGE_ADDRESS,
            'change_time' => time(),
            'change_message' => '审核变更订单收货信息：【' . ($state ? '通过' : '拒绝') . '】' . $reason,
        ]);

        // 更新订单状态
        return $storeOrder->db()->transaction(function () use ($state, $reason, $force, $storeOrder, $orderChangeAddress, $reprint) {
            if ($state) {
                $userAddress = new UserAddress(json_decode($orderChangeAddress->change_user_address_object, true));
                $change_data = $this->extractUpdatingOrderData($userAddress);
                // 判断地址与电话是否一致
                if ($this->isSameAddress($change_data)) {
                    $storeOrder->change_user_address_id = 0;
                    $storeOrder->save();
                    $orderChangeAddress->setLocked();
                    return true;
                }

                // 未呼叫骑手，自动审核通过
                if ($this->autoAuditChangeAddress()) {
                    $storeOrder->change_user_address_id = 0;
                    $storeOrder->user_address_object = json_encode($userAddress, JSON_UNESCAPED_UNICODE);
                    $storeOrder->save($change_data);
                } else {
                    switch ($storeOrder->wechat_service_trans_id) {
                        case ServiceTransEnums::TRANS_SHANSONG:
                            $this->modifyShansongAddress($change_data, $userAddress, $force);
                            break;
                        case ServiceTransEnums::TRANS_SFTC:
                        case ServiceTransEnums::TRANS_DADA:
                        default:
                            $this->modifyWechatIntraCityAddress($change_data, $userAddress, $force, $reason);
                            break;
                    }
                }

                $orderChangeAddress->setLocked();
                // 重新打印票据
                $this->reprintOrderTicket($reprint);
            } else {
                // 拒绝变更地址
                $storeOrder->change_user_address_id = 0;
                $storeOrder->save();

                // 退款
                $refund_reason = '审核拒绝变更收货信息' . ($reason ? '：' . $reason : '');
                if ($orderChangeAddress->canRefund()) {
                    $changeAddressService = new StoreOrderChangeAddressService($orderChangeAddress);
                    $changeAddressService->refund($refund_reason);
                } else {
                    $orderChangeAddress->refund_reason = $refund_reason;
                    $orderChangeAddress->setLocked();
                }
            }

            return true;
        });
    }

    /**
     * 重新打印订单票据
     * @param bool $reprint 是否重新打印
     * @return void
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    final protected function reprintOrderTicket(bool $reprint = false): void
    {
        if ($reprint) {
            /** @var StoreOrderServices $storeOrderService */
            $storeOrderService = app()->make(StoreOrderServices::class);
            $storeOrderService->orderPrintTicket($this->getStoreOrder()->id);
        }
    }

    /**
     * 变更微信同城配送订单的收货信息
     * @param array $change_data
     * @param UserAddress $userAddress
     * @param bool $force
     * @param string $reason
     * @return void
     * @throws ErrorException
     */
    final protected function modifyWechatIntraCityAddress(array $change_data, UserAddress $userAddress, bool $force, string $reason)
    {
        if (!$force) {
            throw new InvalidArgumentException('取消微信同城配送订单时，需要传入强制操作参数');
        }

        $storeOrder = $this->getStoreOrder();
        $api = WechatIntraCityHelper::api();
        $cancel_reason_id = 2;
        $result = $api->cancelOrder($storeOrder->wechat_wx_order_id, $cancel_reason_id, $reason);
        Log::notice('变更微信同城配送订单收货信息：' . json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 变更闪送订单的收货信息
     * @param array $change_data
     * @param UserAddress $userAddress
     * @param bool $force
     * @return void
     */
    final protected function modifyShansongAddress(array $change_data, UserAddress $userAddress, bool $force)
    {
        $storeOrder = $this->getStoreOrder();
        $shansongService = new ShansongService();
        if ($this->isSameAddress($change_data, ['real_name', 'user_phone']) && false === $this->isSamePhone($storeOrder->user_phone, $change_data['user_phone'])) {
            // 变更手机号
            $result = $shansongService->updateToMobile($storeOrder, $change_data['user_phone']);
            $storeOrder->change_user_address_id = 0;
            $storeOrder->save();
        } else {
            // 变更地址
            $result = $shansongService->abortOrder($storeOrder->wechat_trans_order_id, $force);
            $storeOrder->change_user_address_id = 0;
            $storeOrder->user_address_object = json_encode($userAddress, JSON_UNESCAPED_UNICODE);
            $storeOrder->save($change_data);
        }
        Log::notice('变更闪送订单收货信息：' . json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 合并地址信息
     * @param array|UserAddress $addressInfo
     * @return string
     */
    final public static function implodeFullUserAddress($addressInfo): string
    {
        return $addressInfo['map_address'] . ' ' . $addressInfo['map_name'] . ' ' . $addressInfo['detail'];
    }

    /**
     * 提取更新数据
     * @param UserAddress $userAddress
     * @return array
     */
    final public function extractUpdatingOrderData(UserAddress $userAddress): array
    {
        return [
            'real_name' => $userAddress->real_name,
            'user_phone' => $userAddress->phone,
            'user_address' => self::implodeFullUserAddress($userAddress),
            'user_lng' => $userAddress->longitude ?: 0,
            'user_lat' => $userAddress->latitude ?: 0,
        ];
    }

    /**
     * 自动审核变更地址
     * @return bool
     */
    public function autoAuditChangeAddress(): bool
    {
        // 规则1：未呼叫骑手，自动审核通过
        if (!$this->getStoreOrder()->wechat_processed) {
            return true;
        }
        return false;
    }

    /**
     * 判断手机号是否一致
     * @param string $user_phone
     * @param string $change_user_phone
     * @return bool
     */
    final public function isSamePhone(string $user_phone, string $change_user_phone): bool
    {
        return $user_phone === $change_user_phone;
    }

    /**
     * 判断地址与电话是否一致
     * @param array $change_data 变更数据
     * @param array $except 不参与对比的key
     * @return bool
     */
    final public function isSameAddress(array $change_data, array $except = []): bool
    {
        $storeOrder = $this->getStoreOrder();
        foreach ($change_data as $key => $value) {
            if (in_array($key, $except)) {
                continue;
            }
            if ($storeOrder->{$key} !== $value) {
                return false;
            }
        }
        return true;
    }
}
