<?php

namespace Ledc\CrmebIntraCity\services;

use app\dao\order\StoreOrderDao;
use app\model\order\StoreOrder;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderTakeServices;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\parameters\ShanSongParameters;
use Ledc\CrmebIntraCity\ServiceTransEnums;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use Throwable;

/**
 * 配送服务
 */
class DeliveryServices
{
    /**
     * 创建配送单
     * @param int $id 订单主键
     * @param string $service_trans_id 配送运力ID
     * @param array $params 配送单参数
     * @return array
     */
    public static function create(int $id, string $service_trans_id, array $params): array
    {
        try {
            /** @var StoreOrder $order */
            $order = StoreOrder::findOrEmpty($id);
            if ($order->isEmpty()) {
                throw new ValidateException('订单不存在');
            }

            switch ($service_trans_id) {
                case ServiceTransEnums::TRANS_SHANSONG:
                    $service = new ShanSongService();
                    $shanSongParameters = ShanSongParameters::make($params)->setStoreOrder($order)->cache();
                    $rs = $service->orderPlace(
                        $order,
                        $shanSongParameters
                    )->jsonSerialize();
                    break;
                case ServiceTransEnums::TRANS_SFTC:
                case ServiceTransEnums::TRANS_DADA:
                default:
                    $crmebStore = new WechatIntraCityService();
                    $rs = get_object_vars($crmebStore->createOrder($order));
                    break;
            }

            return $rs;
        } catch (Throwable $throwable) {
            throw new ValidateException($throwable->getMessage());
        }
    }

    /**
     * 配送单已取消
     * @param StoreOrder $storeOrder
     * @return bool
     */
    public static function doCancelled(StoreOrder $storeOrder): bool
    {
        $storeOrder->wechat_processed = 0;
        $storeOrder->trans_order_status = TransOrderStatusEnums::Cancelled;
        $storeOrder->delivery_code = '';
        $storeOrder->delivery_type = '';
        $storeOrder->delivery_name = '';
        $storeOrder->delivery_id = '';
        $storeOrder->save();

        // 提醒客服
        WechatTemplateService::sendAdminOrderException($storeOrder);
        return true;
    }

    /**
     * 订单发货
     * - 发货逻辑参考自 \app\services\order\StoreOrderDeliveryServices::doDelivery
     * @param StoreOrder $orderInfo
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function doDelivery(StoreOrder $orderInfo): bool
    {
        $orderInfo->trans_order_status = TransOrderStatusEnums::InTransit;
        $orderInfo->save();

        // 类型：1快递发货、2配送、3虚拟发货
        $type = 2;
        $data = [
            'delivery_type' => $orderInfo->wechat_service_trans_id,
            'delivery_name' => $orderInfo->wechat_service_trans_id,
            'delivery_id' => $orderInfo->wechat_trans_order_id,
            'delivery_uid' => 0,
            'shipping_type' => 1,
            'status' => 1,
        ];

        /** @var StoreOrderDao $storeOrderDao */
        $storeOrderDao = app()->make(StoreOrderDao::class);
        //获取购物车内的商品标题
        /** @var StoreOrderCartInfoServices $orderInfoServices */
        $orderInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $storeName = $orderInfoServices->getCarIdByProductTitle((int)$orderInfo->id);

        // 更新订单
        $storeOrderDao->update($orderInfo->id, $data);

        event('NoticeListener', [['orderInfo' => $orderInfo, 'storeName' => $storeName, 'data' => $data], 'order_deliver_success']);

        //自定义消息-配送员配送
        $orderInfo['storeName'] = $storeName;
        $orderInfo['delivery_name'] = $data['delivery_name'];
        $orderInfo['delivery_id'] = $data['delivery_id'];
        $orderInfo['time'] = date('Y-m-d H:i:s');
        $orderInfo['phone'] = $orderInfo['user_phone'];
        event('CustomNoticeListener', [$orderInfo['uid'], $orderInfo, 'order_send_success']);

        // 小程序订单管理
        event('OrderShippingListener', ['product', $orderInfo, $type, $data['delivery_id'], $data['delivery_name']]);
        //到期自动收货
        event('OrderDeliveryListener', [$orderInfo, $storeName, $data, $type]);

        //自定义事件-订单发货
        event('CustomEventListener', ['admin_order_express', [
            'uid' => $orderInfo['uid'],
            'real_name' => $orderInfo['real_name'],
            'user_phone' => $orderInfo['user_phone'],
            'user_address' => $orderInfo['user_address'],
            'order_id' => $orderInfo['order_id'],
            'delivery_name' => $orderInfo['delivery_name'],
            'delivery_id' => $orderInfo['delivery_id'],
            'express_time' => date('Y-m-d H:i:s'),
        ]]);

        return true;
    }

    /**
     * 订单配送完成，自动确认收货
     * @param StoreOrder $storeOrder
     * @return void
     */
    public static function doCompleted(StoreOrder $storeOrder): void
    {
        $status = $storeOrder->status;
        $paid = $storeOrder->paid;
        $pay_type = $storeOrder->getAttr('pay_type');
        if (
            (1 === (int)$status && 1 === (int)$paid)
            || 'offline' === $pay_type
        ) {
            /** @var StoreOrder $order */
            $order = StoreOrder::findOrEmpty($storeOrder->id);
            $order->status = 2;
            $order->trans_order_status = TransOrderStatusEnums::Completed;
            $order->save();
            /** @var StoreOrderTakeServices $storeOrderTakeServices */
            $storeOrderTakeServices = app()->make(StoreOrderTakeServices::class);
            $storeOrderTakeServices->storeProductOrderUserTakeDelivery($order);
        }
    }
}
