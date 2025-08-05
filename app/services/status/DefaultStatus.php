<?php

namespace Ledc\CrmebIntraCity\services\status;

use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 默认状态
 */
class DefaultStatus extends OrderStatusBase
{
    /**
     * 获取订单状态
     * @return int
     */
    public function getStatus(): int
    {
        return TransOrderStatusEnums::DEFAULT;
    }

    /**
     * 转换到默认状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toDefault(TransOrderContext $context): void
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
}
