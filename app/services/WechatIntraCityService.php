<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\order\StoreOrder;
use app\model\wechat\WechatUser;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderStatusServices;
use ErrorException;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\WechatIntraCityHelper;
use Ledc\IntraCity\Contracts\CallableNotify;
use Ledc\IntraCity\Contracts\CargoPayload;
use Ledc\IntraCity\Contracts\OrderPayload;
use Ledc\IntraCity\Contracts\OrderResponse;
use Ledc\IntraCity\Contracts\PreviewOrderPayload;
use Ledc\IntraCity\Contracts\PreviewOrderResponse;
use Ledc\IntraCity\Enums\CargoTypeEnums;
use Ledc\IntraCity\Enums\OrderStatusEnums;
use Ledc\IntraCity\ExpressApi;
use RuntimeException;
use think\facade\Db;
use think\facade\Log;
use Throwable;

/**
 * Crmeb单商户系统
 */
class WechatIntraCityService
{
    /**
     * @var ExpressApi
     */
    protected ExpressApi $api;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->api = WechatIntraCityHelper::api();
    }

    /**
     * @return ExpressApi
     */
    public function getApi(): ExpressApi
    {
        return $this->api;
    }

    /**
     * 查询运费
     * @param string $sumPrice 商品总价（单位：元）
     * @param array $cartInfo 购物车
     * @param array $addr 用户地址
     * @return PreviewOrderResponse
     * @throws ErrorException
     * @throws Throwable
     */
    public function previewAddOrder(string $sumPrice, array $cartInfo, array $addr): PreviewOrderResponse
    {
        try {
            $cargo_weight = 0;
            $cargoPayload = new CargoPayload();
            $cargoPayload->cargo_name = sys_config('site_name');
            $cargoPayload->cargo_price = $sumPrice * 100;
            // 枚举值：鲜花或蛋糕
            $cargoPayload->cargo_type = $this->getCargoType($cartInfo);
            $cargoPayload->cargo_num = array_sum(array_column($cartInfo, 'cart_num'));
            $cargoPayload->item_list = array_map(function ($item) use (&$cargo_weight) {
                $weight = isset($item['attrInfo']['weight']) && $item['attrInfo']['weight'] ? $item['attrInfo']['weight'] : 0;
                $cargo_weight = bcadd($cargo_weight, bcmul($item['cart_num'], $weight));
                return [
                    'item_name' => $item['productInfo']['store_name'],
                    'item_pic_url' => $item['productInfo']['image'],
                    'count' => $item['cart_num']
                ];
            }, $cartInfo);
            $cargoPayload->cargo_weight = $cargo_weight ? $cargo_weight * 1000 : 500;

            $previewOrderPayload = new PreviewOrderPayload();
            $previewOrderPayload->wx_store_id = $this->getApi()->getConfig()->getWxStoreId();
            $previewOrderPayload->user_name = $addr['real_name'];
            $previewOrderPayload->user_phone = $addr['phone'];
            $previewOrderPayload->user_lng = $addr['longitude'];
            $previewOrderPayload->user_lat = $addr['latitude'];
            $previewOrderPayload->user_address = implode('', [$addr['city'], $addr['district'], $addr['detail']]);
            $previewOrderPayload->cargo = $cargoPayload;
            $previewOrderPayload->use_sandbox = $this->getApi()->getConfig()->isUseSandbox();
            Log::record('查询配送费：' . json_encode($previewOrderPayload->jsonSerialize(), JSON_UNESCAPED_UNICODE), 'notice');
            return $this->getApi()->previewAddOrder($previewOrderPayload);
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            throw $throwable;
        }
    }

    /**
     * 获取同城配送商品类型
     * @tips 商品中包含蛋糕，返回蛋糕类型；否则返回默认值鲜花
     * @param array $cartInfo
     * @return int
     */
    public static function getCargoType(array $cartInfo): int
    {
        foreach ($cartInfo as $item) {
            $cart_row = $item;
            if (empty($item['productInfo']) && isset($item['cart_info']) && is_string($item['cart_info'])) {
                $cart_row = json_decode($item['cart_info'], true);
            }

            $cargo_type = $cart_row['productInfo']['cargo_type'] ?? 0;
            // 商品中包含蛋糕，返回蛋糕类型
            if ($cargo_type && CargoTypeEnums::INT_13 === (int)$cargo_type) {
                return $cargo_type;
            }
        }

        return CargoTypeEnums::INT_14;
    }

    /**
     * 创建配送单
     * @param StoreOrder $order
     * @return OrderResponse
     * @throws ErrorException
     * @throws Throwable
     */
    public function createOrder(StoreOrder $order): OrderResponse
    {
        try {
            CreateOrderValidate::beforeValidate($order);

            /**
             * 创建配送单
             */
            $sumPrice = $order['total_price'];
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $cartInfo = $cartServices->getCartColunm(['oid' => $order['id']], 'cart_num,surplus_num,cart_info,refund_num', 'unique');
            $cargo_weight = 0;
            $cargoPayload = new CargoPayload();
            $cargoPayload->cargo_name = sys_config('site_name');
            $cargoPayload->cargo_price = $sumPrice * 100;
            // 枚举值：鲜花或蛋糕
            $cargoPayload->cargo_type = $this->getCargoType($cartInfo);
            $cargoPayload->cargo_num = array_sum(array_column($cartInfo, 'cart_num'));
            $cargoPayload->item_list = array_values(array_map(function ($item) use (&$cargo_weight) {
                $_item = json_decode($item['cart_info'], true);
                $weight = isset($_item['attrInfo']['weight']) && $_item['attrInfo']['weight'] ? $_item['attrInfo']['weight'] : 0;
                $cargo_weight = bcadd($cargo_weight, bcmul($_item['cart_num'], $weight));
                return [
                    'item_name' => $_item['productInfo']['store_name'],
                    'item_pic_url' => $_item['productInfo']['image'],
                    'count' => $_item['cart_num']
                ];
            }, $cartInfo));
            $cargoPayload->cargo_weight = $cargo_weight ? $cargo_weight * 1000 : 500;

            $orderPayload = new OrderPayload();
            $orderPayload->wx_store_id = $this->getApi()->getConfig()->getWxStoreId();
            $orderPayload->store_order_id = $order['order_id'];
            $orderPayload->user_openid = WechatUser::where('uid', $order['uid'])->value('openid') ?: '';
            $orderPayload->user_name = $order['real_name'];
            $orderPayload->user_phone = $order['user_phone'];
            $orderPayload->user_lng = $order['user_lng'];
            $orderPayload->user_lat = $order['user_lat'];
            $orderPayload->user_address = $order['user_address'];
            $orderPayload->order_seq = get_order_seq($order['pay_time'], $order['order_seq']);
            //$previewOrderPayload->verify_code_type = $order['verify_code_type'] ?: 0;
            $orderPayload->order_detail_path = $this->getApi()->getConfig()->getOrderDetailPath();
            $orderPayload->callback_url = $this->getApi()->getConfig()->getCallbackUrl();
            $orderPayload->use_sandbox = $this->getApi()->getConfig()->isUseSandbox() ? 1 : 0;
            $orderPayload->cargo = $cargoPayload;

            Log::record('创建配送单：' . json_encode($orderPayload->jsonSerialize(), JSON_UNESCAPED_UNICODE), 'notice');
            $orderResponse = $this->getApi()->addOrder($orderPayload);

            /** @var StoreOrderStatusServices $services */
            $services = app()->make(StoreOrderStatusServices::class);
            //记录订单状态
            $services->save([
                'oid' => $order->id,
                'change_type' => OrderChangeTypeEnums::CITY_CREATE_ORDER,
                'change_time' => time(),
                'change_message' => '呼叫同城配送，运力：' . $orderResponse->service_trans_id . ' 运力订单号：' . $orderResponse->trans_order_id . ' 时间：' . date('Y-m-d H:i:s'),
            ]);

            /**
             * 更新数据库
             */
            Db::transaction(function () use ($order, $orderPayload, $orderResponse) {
                $order->wechat_wx_store_id = $orderResponse->wx_store_id;
                $order->wechat_wx_order_id = $orderResponse->wx_order_id;
                $order->wechat_service_trans_id = $orderResponse->service_trans_id;
                $order->wechat_distance = $orderResponse->distance;
                $order->wechat_trans_order_id = $orderResponse->trans_order_id;
                $order->wechat_waybill_id = $orderResponse->waybill_id;
                $order->wechat_fee = $orderResponse->fee;
                $order->wechat_fetch_code = $orderResponse->fetch_code;
                $order->wechat_processed = 1;
                $order->trans_order_create_time = time();
                $order->trans_order_update_time = time();
                $order->save();
            });

            return $orderResponse;
        } catch (Throwable $throwable) {
            Log::error($throwable->getMessage());
            throw $throwable;
        }
    }

    /**
     * 处理回调通知
     * @param CallableNotify $notify
     * @return bool
     */
    public function notifyCallback(CallableNotify $notify): bool
    {
        $wx_order_id = $notify->wx_order_id;
        /** @var StoreOrder $order */
        $order = StoreOrder::where('wechat_wx_order_id', '=', $wx_order_id)->findOrEmpty();
        if ($order->isEmpty()) {
            return false;
        }

        if ($order->wechat_order_status == $notify->order_status) {
            return true;
        }

        Db::transaction(function () use ($order, $notify) {
            /** @var StoreOrderStatusServices $services */
            $services = app()->make(StoreOrderStatusServices::class);

            // 更新订单
            $order->wechat_order_status = $notify->order_status;
            $order->save();

            //记录订单状态
            $services->save([
                'oid' => $order->id,
                'change_type' => OrderChangeTypeEnums::CITY_NOTIFY_CALLBACK,
                'change_time' => time(),
                'change_message' => '运力：' . $notify->service_trans_id . ' 订单状态变更：【' . OrderStatusEnums::text($notify->order_status) . '】' . date('Y-m-d H:i:s', $notify->status_change_time),
            ]);
        });

        // 配送完成，自动收货
        Db::transaction(function () use ($order, $notify) {
            if (OrderStatusEnums::UINT_70000 === $notify->order_status) {
                DeliveryServices::doCompleted($order);
            }
        });

        return true;
    }

    /**
     * 取消配送单
     * @param StoreOrder $order
     * @param int $cancel_reason_id 取消原因（1:不需要了、2：信息填错、3：无人接单、99：其他）
     * @param string $cancel_reason 取消原因描述
     * @return array
     * @throws ErrorException
     */
    public function cancelDelivery(StoreOrder $order, int $cancel_reason_id, string $cancel_reason = ''): array
    {
        $expressApi = wechat_express_api();
        if (!$order->wechat_wx_order_id) {
            throw new RuntimeException('微信订单编号为空，无法取消配送单');
        }
        // TODO:其他验证逻辑
        return $expressApi->cancelOrder($order->wechat_wx_order_id, $cancel_reason_id, $cancel_reason);
    }
}
