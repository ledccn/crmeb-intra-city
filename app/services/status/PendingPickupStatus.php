<?php

namespace Ledc\CrmebIntraCity\services\status;

use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 待取货状态
 */
class PendingPickupStatus extends OrderStatusBase
{
    /**
     * 获取订单状态
     * @return int
     */
    public function getStatus(): int
    {
        return TransOrderStatusEnums::PendingPickup;
    }

    /**
     * 转换到待取货状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toPendingPickup(TransOrderContext $context): void
    {
        // do nothing
    }

    /**
     * 转换到派单中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toAssigned(TransOrderContext $context): void
    {
        $storeOrder = $context->getStoreOrder();
        $storeOrder->trans_order_status = TransOrderStatusEnums::Assigned;
        $storeOrder->trans_order_create_time = time();
        $storeOrder->trans_order_update_time = time();
        $storeOrder->save();
    }

    /**
     * 转换到送货中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toInTransit(TransOrderContext $context): void
    {
        $storeOrder = $context->getStoreOrder();
        $storeOrder->trans_order_status = TransOrderStatusEnums::InTransit;
        $storeOrder->trans_order_update_time = time();
        $storeOrder->save();
    }

    /**
     * 转换到已取消状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCancelled(TransOrderContext $context): void
    {
        $storeOrder = $context->getStoreOrder();
        $storeOrder->trans_order_status = TransOrderStatusEnums::Cancelled;
        $storeOrder->trans_order_update_time = time();
        $storeOrder->save();
    }
}
