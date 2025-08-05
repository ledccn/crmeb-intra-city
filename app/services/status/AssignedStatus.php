<?php

namespace Ledc\CrmebIntraCity\services\status;

use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 派单中状态
 */
class AssignedStatus extends OrderStatusBase
{
    /**
     * 获取订单状态
     * @return int
     */
    public function getStatus(): int
    {
        return TransOrderStatusEnums::Assigned;
    }

    /**
     * 转换到派单中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toAssigned(TransOrderContext $context): void
    {
        // do nothing
    }

    /**
     * 转换到待取货状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toPendingPickup(TransOrderContext $context): void
    {
        $storeOrder = $context->getStoreOrder();
        $storeOrder->trans_order_status = TransOrderStatusEnums::PendingPickup;
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
