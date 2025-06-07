<?php

namespace Ledc\CrmebIntraCity\observer\shansong;

use app\model\order\StoreOrderStatus;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\services\DeliveryServices;
use Ledc\CrmebIntraCity\services\WechatTemplateService;
use SplObserver;
use SplSubject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 记录日志
 */
class OrderObserver implements SplObserver
{
    /**
     * @param SplSubject|Subject $subject
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function update(SplSubject $subject): void
    {
        $notify = $subject->getStatus();
        $storeOrder = $subject->getStoreOrder();

        // 记录订单变更日志
        StoreOrderStatus::create([
            'oid' => $storeOrder->id,
            'change_type' => OrderChangeTypeEnums::CITY_NOTIFY_CALLBACK,
            'change_time' => time(),
            'change_message' => '闪送 订单状态变更：【' . $notify->statusDesc . '(' . $notify->subStatusDesc . ')】',
        ]);

        // 更新订单状态
        $storeOrder->trans_order_update_time = time();
        $storeOrder->wechat_order_status = $notify->status;
        $storeOrder->wechat_order_sub_status = $notify->subStatus;
        $storeOrder->save();
        switch (true) {
            case $notify->isCancelled():
            case $notify->isCompletedRefund():
                DeliveryServices::doCancelled($storeOrder);
                break;
            case $notify->isRiderAcceptedAndAwaitingPickup():
                $storeOrder->trans_order_status = TransOrderStatusEnums::PendingPickup;
                $storeOrder->wechat_fetch_code = $notify->deliveryPassword;
                if ($courier = $notify->courier) {
                    $storeOrder->delivery_code = $storeOrder->wechat_service_trans_id;
                    $storeOrder->delivery_type = $storeOrder->wechat_service_trans_id;
                    $storeOrder->delivery_name = $courier->name . '|' . $courier->mobile;
                    $storeOrder->delivery_id = $storeOrder->wechat_trans_order_id;
                }
                $storeOrder->save();
                break;
            case $notify->isDelivering():
                DeliveryServices::doDelivery($storeOrder);
                break;
            case $notify->isCompleted():
                DeliveryServices::doCompleted($storeOrder);
                break;
            default:
                break;
        }
    }
}
