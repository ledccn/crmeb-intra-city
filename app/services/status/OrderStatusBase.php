<?php

namespace Ledc\CrmebIntraCity\services\status;

use InvalidArgumentException;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 订单状态基础类
 */
abstract class OrderStatusBase implements OrderStatusInterface
{
    /**
     * 创建订单状态转换类
     * @param int $status
     * @return OrderStatusInterface
     */
    final public static function make(int $status): OrderStatusInterface
    {
        switch (true) {
            case TransOrderStatusEnums::DEFAULT === $status:
                return new DefaultStatus();
            case TransOrderStatusEnums::Assigned === $status:
                return new AssignedStatus();
            case TransOrderStatusEnums::PendingPickup === $status:
                return new PendingPickupStatus();
            case TransOrderStatusEnums::InTransit === $status:
                return new InTransitStatus();
            case TransOrderStatusEnums::Completed === $status:
                return new CompletedStatus();
            case TransOrderStatusEnums::Cancelled === $status:
                return new CancelledStatus();
            default:
                throw new InvalidArgumentException('无效的状态值');
        }
    }

    /**
     * 转换到默认状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toDefault(TransOrderContext $context): void
    {
        throw new InvalidArgumentException('禁止转换到默认状态');
    }

    /**
     * 转换到派单中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toAssigned(TransOrderContext $context): void
    {
        throw new InvalidArgumentException('禁止转换到派单中状态');
    }

    /**
     * 转换到待取货状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toPendingPickup(TransOrderContext $context): void
    {
        throw new InvalidArgumentException('禁止转换到待取货状态');
    }

    /**
     * 转换到送货中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toInTransit(TransOrderContext $context): void
    {
        throw new InvalidArgumentException('禁止转换到送货中状态');
    }

    /**
     * 转换到已完成状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCompleted(TransOrderContext $context): void
    {
        throw new InvalidArgumentException('禁止转换到已完成状态');
    }

    /**
     * 转换到已取消状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCancelled(TransOrderContext $context): void
    {
        throw new InvalidArgumentException('禁止转换到已取消状态');
    }
}
