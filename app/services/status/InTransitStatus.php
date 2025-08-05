<?php

namespace Ledc\CrmebIntraCity\services\status;

use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 送货中状态
 */
class InTransitStatus extends OrderStatusBase
{
    /**
     * 获取订单状态
     * @return int
     */
    public function getStatus(): int
    {
        return TransOrderStatusEnums::InTransit;
    }

    /**
     * 转换到送货中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toInTransit(TransOrderContext $context): void
    {
        // do nothing
    }

    /**
     * 转换到已完成状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCompleted(TransOrderContext $context): void
    {
        $storeOrder = $context->getStoreOrder();
        $storeOrder->trans_order_status = TransOrderStatusEnums::Completed;
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
