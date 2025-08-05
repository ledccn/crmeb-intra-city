<?php

namespace Ledc\CrmebIntraCity\services\status;

use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 已取消状态
 */
class CancelledStatus extends OrderStatusBase
{
    /**
     * 获取订单状态
     * @return int
     */
    public function getStatus(): int
    {
        return TransOrderStatusEnums::Cancelled;
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
     * 转换到已取消状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCancelled(TransOrderContext $context): void
    {
        // do nothing
    }
}
