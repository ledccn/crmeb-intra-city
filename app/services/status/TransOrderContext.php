<?php

namespace Ledc\CrmebIntraCity\services\status;

use app\model\order\StoreOrder;
use BadMethodCallException;

/**
 * 同城配送订单，状态转换上下文
 */
class TransOrderContext
{
    /**
     * 订单状态
     * @var OrderStatusInterface
     */
    private OrderStatusInterface $status;
    /**
     * 订单信息
     * @var StoreOrder
     */
    private StoreOrder $storeOrder;

    /**
     * 构造函数
     * @param OrderStatusInterface $status
     * @param StoreOrder $storeOrder
     */
    public function __construct(OrderStatusInterface $status, StoreOrder $storeOrder)
    {
        $this->status = $status;
        $this->storeOrder = $storeOrder;
    }

    /**
     * 创建订单状态转换上下文对象
     * @param StoreOrder $storeOrder
     * @return TransOrderContext
     */
    final public static function make(StoreOrder $storeOrder): TransOrderContext
    {
        $trans_order_status = $storeOrder->trans_order_status;
        return new static(OrderStatusBase::make($trans_order_status), $storeOrder);
    }

    /**
     * 转换到默认状态
     * @return void
     */
    final public function toDefault(): void
    {
        $this->status->toDefault($this);
    }

    /**
     * 转换到派单中状态
     * @return void
     */
    final public function toAssigned(): void
    {
        $this->status->toAssigned($this);
    }

    /**
     * 转换到待取货状态
     * @return void
     */
    final public function toPendingPickup(): void
    {
        $this->status->toPendingPickup($this);
    }

    /**
     * 转换到送货中状态
     * @return void
     */
    final public function toInTransit(): void
    {
        $this->status->toInTransit($this);
    }

    /**
     * 转换到已完成状态
     * @return void
     */
    final public function toCompleted(): void
    {
        $this->status->toCompleted($this);
    }

    /**
     * 转换到已取消状态
     * @return void
     */
    final public function toCancelled(): void
    {
        $this->status->toCancelled($this);
    }

    /**
     * 获取订单状态
     * @return OrderStatusInterface
     */
    final public function getStatus(): OrderStatusInterface
    {
        return $this->status;
    }

    /**
     * 设置订单状态
     * @param OrderStatusInterface $status
     * @return void
     */
    final public function setStatus(OrderStatusInterface $status): void
    {
        $this->status = $status;
    }

    /**
     * 获取订单信息
     * @return StoreOrder
     */
    public function getStoreOrder(): StoreOrder
    {
        return $this->storeOrder;
    }

    /**
     * 动态调用
     * @param string $name
     * @param array $arguments
     * @return void
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->status, $name) && is_callable([$this->status, $name])) {
            return call_user_func([$this->status, $name], $this, ...$arguments);
        }
        throw new BadMethodCallException("Method $name not exists");
    }
}
