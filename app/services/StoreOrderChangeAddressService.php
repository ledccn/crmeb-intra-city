<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\order\StoreOrderStatus;
use app\services\pay\OrderPayServices;
use app\services\pay\PayServices;
use app\services\wechat\WechatUserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use crmeb\services\pay\Pay;
use crmeb\utils\Str;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\model\EbStoreOrderChangeAddress;
use think\exception\ValidateException;
use think\facade\Event;
use think\facade\Log;
use Throwable;

/**
 * 变更地址订单服务
 */
class StoreOrderChangeAddressService
{
    /**
     * @var EbStoreOrderChangeAddress
     */
    protected EbStoreOrderChangeAddress $orderChangeAddress;

    /**
     * 构造函数
     * @param EbStoreOrderChangeAddress $orderChangeAddress
     */
    public function __construct(EbStoreOrderChangeAddress $orderChangeAddress)
    {
        $this->orderChangeAddress = $orderChangeAddress;
    }

    /**
     * 获取订单
     * @return EbStoreOrderChangeAddress
     */
    public function getOrderChangeAddress(): EbStoreOrderChangeAddress
    {
        return $this->orderChangeAddress;
    }

    /**
     * 支付成功
     * @param string $trade_no 微信支付订单号
     * @param string $payType 支付方式
     * @return bool
     */
    public function paySuccess(string $trade_no, string $payType): bool
    {
        $orderChangeAddress = $this->orderChangeAddress;
        $orderChangeAddress->setPaidStatus(true);
        $orderChangeAddress->pay_type = $payType;
        $orderChangeAddress->pay_trade_no = $trade_no;
        $orderChangeAddress->save();

        Event::trigger('EbStoreOrderChangeAddress.paySuccess', [$orderChangeAddress]);

        return true;
    }

    /**
     * 生成补差价订单的支付参数
     * @param array $options
     * @return array
     */
    public function pay(array $options): array
    {
        $orderChangeAddress = $this->orderChangeAddress;
        if ($orderChangeAddress->isPaid()) {
            throw new ApiException(410174);
        }
        if ($orderChangeAddress->pay_price <= 0) {
            throw new ApiException(410274);
        }
        $storeOrder = $orderChangeAddress->getStoreOrder();
        if (!$storeOrder) {
            $orderChangeAddress->setLocked();
            throw new ValidateException('订单不存在');
        }
        if ($storeOrder->change_user_address_id !== $orderChangeAddress->id) {
            $orderChangeAddress->setLocked();
            throw new ValidateException('订单已过期');
        }
        if (!TransOrderStatusEnums::isAllowChangeAddressOrExpectedFinishedTime($storeOrder->trans_order_status)) {
            $orderChangeAddress->setLocked();
            throw new ValidateException('订单状态不允许支付');
        }

        // 指定为微信支付
        $payType = PayServices::WEIXIN_PAY;
        $orderChangeAddress->pay_type = $payType;
        $orderChangeAddress->save();

        $openid = '';
        if (request()->isWechat() || request()->isRoutine()) {
            if (request()->isWechat()) {
                $userType = 'wechat';
            } else {
                $userType = 'routine';
            }
            /** @var WechatUserServices $services */
            $services = app()->make(WechatUserServices::class);
            $openid = $services->uidToOpenid($orderChangeAddress->uid, $userType);
            if (!$openid) {
                throw new ApiException(410275);
            }
        }
        $options['openid'] = $openid;
        $site_name = sys_config('site_name');
        $body = Str::substrUTf8($site_name . '--' . '补配送费差价', 20);
        $successAction = EbStoreOrderChangeAddress::PAY_SUCCESS_ACTION;
        $orderPayServices = app()->make(OrderPayServices::class);
        /** @var PayServices $payServices */
        $payServices = app()->make(PayServices::class);
        //发起支付
        $jsConfig = $payServices->pay($payType, $orderChangeAddress->order_number, $orderChangeAddress->pay_price, $successAction, $body, $options);
        //发起支付后处理返回参数
        $orderInfo = [
            'id' => $orderChangeAddress->id,
            'order_id' => $orderChangeAddress->order_number,
        ];
        $payInfo = $orderPayServices->afterPay($orderInfo, $jsConfig, $payType);
        $statusType = $orderPayServices->payStatus($payType);

        return [$statusType, $payInfo];
    }

    /**
     * 退款
     * @param string $refund_reason
     * @return void
     */
    public function refund(string $refund_reason): void
    {
        try {
            $orderChangeAddress = $this->orderChangeAddress;
            if (!$orderChangeAddress->isPaid()) {
                throw new ValidateException('订单未支付，无法退款');
            }
            if ($orderChangeAddress->pay_type !== PayServices::WEIXIN_PAY) {
                throw new ValidateException('非微信支付，无法退款');
            }
            if ($orderChangeAddress->pay_price <= 0) {
                throw new ValidateException('订单金额小于等于0，无法退款');
            }

            /** @var WechatUserServices $wechatUserServices */
            $wechatUserServices = app()->make(WechatUserServices::class);
            if (request()->isWechat()) {
                $userType = 'wechat';
            } else {
                $userType = 'routine';
            }
            $trade_no = $orderChangeAddress->pay_trade_no;
            $refund_no = 'tk' . generate_order_number();
            $refund_data = [
                'type' => 'trade_no',
                'trade_no' => $trade_no,
                'pay_price' => $orderChangeAddress->pay_price,
                'refund_price' => $orderChangeAddress->pay_price,
                'order_id' => $orderChangeAddress->order_number,
                'open_id' => $wechatUserServices->uidToOpenid((int)$orderChangeAddress->uid, $userType) ?: '',
                'pay_new_weixin_open' => sys_config('pay_new_weixin_open'),
                'refund_no' => $refund_no,
                'desc' => $refund_reason,
                'refund_reason' => $refund_reason,
            ];

            if (sys_config('pay_wechat_type')) {
                $drivers = 'v3_wechat_pay';
            } else {
                $drivers = 'wechat_pay';
            }
            /** @var Pay $pay */
            $pay = app()->make(Pay::class, [$drivers]);
            $pay->refund($trade_no, $refund_data);

            $orderChangeAddress->refund_status = 1;
            $orderChangeAddress->refund_price = $orderChangeAddress->pay_price;
            $orderChangeAddress->refund_reason = $refund_reason;
            $orderChangeAddress->setLocked();

            // 记录订单变更日志
            StoreOrderStatus::create([
                'oid' => $orderChangeAddress->oid,
                'change_type' => OrderChangeTypeEnums::CHANGE_ADDRESS_REFUND,
                'change_time' => time(),
                'change_message' => '变更地址补差价退款成功，' . "退款金额：" . $orderChangeAddress->pay_price . " 退款单号：" . $refund_no . " 原因：" . $refund_reason,
            ]);
        } catch (Throwable $throwable) {
            Log::error('补差价退款失败：' . $throwable->getMessage());
            throw new AdminException($throwable->getMessage());
        }
    }
}
