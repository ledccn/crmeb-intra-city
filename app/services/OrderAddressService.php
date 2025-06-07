<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\order\StoreOrder;
use app\model\order\StoreOrderStatus;
use app\model\user\UserAddress;
use ErrorException;
use InvalidArgumentException;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\parameters\HasStoreOrder;
use Ledc\CrmebIntraCity\ServiceTransEnums;
use Ledc\CrmebIntraCity\WechatIntraCityHelper;
use think\facade\Log;

/**
 * 订单服务
 */
class OrderAddressService
{
    use HasStoreOrder;

    /**
     * 构造函数
     * @param StoreOrder $storeOrder
     */
    public function __construct(StoreOrder $storeOrder)
    {
        $this->setStoreOrder($storeOrder);
    }

    /**
     * 审核变更地址或电话
     * @param bool $state 审核状态：true 通过，false 拒绝
     * @param string $reason 审核原因
     * @param bool $force 是否强制操作（审核状态true时，取消订单可能产生费用，需传true）
     * @return bool
     */
    public function auditChangeAddress(bool $state, string $reason, bool $force): bool
    {
        $storeOrder = $this->getStoreOrder();
        // 记录订单变更日志
        StoreOrderStatus::create([
            'oid' => $storeOrder->id,
            'change_type' => OrderChangeTypeEnums::CHANGE_ADDRESS,
            'change_time' => time(),
            'change_message' => '审核变更订单收货信息：【' . ($state ? '通过' : '拒绝') . '】' . $reason,
        ]);

        // 更新订单状态
        return $storeOrder->db()->transaction(function () use ($state, $reason, $force, $storeOrder) {
            if ($state) {
                $userAddress = UserAddress::findOrFail($storeOrder->change_user_address_id);
                $change_data = $this->extractUpdatingOrderData($userAddress);
                // 判断地址与电话是否一致
                if ($this->isSameAddress($change_data)) {
                    $storeOrder->change_user_address_id = 0;
                    $storeOrder->save();
                    return true;
                }

                // 未呼叫骑手，自动审核通过
                if ($this->autoAuditChangeAddress()) {
                    $storeOrder->change_user_address_id = 0;
                    $storeOrder->user_address_object = json_encode($userAddress, JSON_UNESCAPED_UNICODE);
                    $storeOrder->save($change_data);
                    return true;
                }

                switch ($storeOrder->wechat_service_trans_id) {
                    case ServiceTransEnums::TRANS_SHANSONG:
                        $this->modifyShansongAddress($change_data, $userAddress, $force);
                        return true;
                    case ServiceTransEnums::TRANS_SFTC:
                    case ServiceTransEnums::TRANS_DADA:
                    default:
                        $this->modifyWechatIntraCityAddress($change_data, $userAddress, $force, $reason);
                        return true;
                }
            } else {
                // 拒绝变更地址
                $storeOrder->change_user_address_id = 0;
                $storeOrder->save();
                return true;
            }
        });
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
    protected function modifyWechatIntraCityAddress(array $change_data, UserAddress $userAddress, bool $force, string $reason)
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
    protected function modifyShansongAddress(array $change_data, UserAddress $userAddress, bool $force)
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
            $result = $shansongService->abortOrder($storeOrder, $force);
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
    public static function implodeFullUserAddress($addressInfo): string
    {
        return $addressInfo['map_address'] . ' ' . $addressInfo['map_name'] . ' ' . $addressInfo['detail'];
    }

    /**
     * 提取更新数据
     * @param UserAddress $userAddress
     * @return array
     */
    public function extractUpdatingOrderData(UserAddress $userAddress): array
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
    public function isSamePhone(string $user_phone, string $change_user_phone): bool
    {
        return $user_phone === $change_user_phone;
    }

    /**
     * 判断地址与电话是否一致
     * @param array $change_data 变更数据
     * @param array $except 不参与对比的key
     * @return bool
     */
    public function isSameAddress(array $change_data, array $except = []): bool
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
