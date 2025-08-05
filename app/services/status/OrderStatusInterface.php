<?php

namespace Ledc\CrmebIntraCity\services\status;

/**
 * 同城配送订单单状态接口
 */
interface OrderStatusInterface
{
    /**
     * 获取订单状态
     * @return int
     */
    public function getStatus(): int;

    /**
     * 转换到默认状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toDefault(TransOrderContext $context): void;

    /**
     * 转换到派单中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toAssigned(TransOrderContext $context): void;

    /**
     * 转换到待取货状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toPendingPickup(TransOrderContext $context): void;

    /**
     * 转换到送货中状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toInTransit(TransOrderContext $context): void;

    /**
     * 转换到已完成状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCompleted(TransOrderContext $context): void;

    /**
     * 转换到已取消状态
     * @param TransOrderContext $context
     * @return void
     */
    public function toCancelled(TransOrderContext $context): void;
}