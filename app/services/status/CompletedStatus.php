<?php

namespace Ledc\CrmebIntraCity\services\status;

use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 已完成状态
 */
class CompletedStatus extends OrderStatusBase
{
    /**
     * 获取订单状态
     * @return int
     */
    public function getStatus(): int
    {
        return TransOrderStatusEnums::Completed;
    }

    /**
     * 转换成已完成状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCompleted(TransOrderContext $context): void
    {
        // do nothing
    }
}
