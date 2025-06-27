<?php

namespace Ledc\CrmebIntraCity\parameters;

use app\model\order\StoreOrder;
use InvalidArgumentException;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;

/**
 * 订单数据模型
 */
trait HasStoreOrder
{
    /**
     * 订单数据模型
     * @var StoreOrder
     */
    private StoreOrder $storeOrder;

    /**
     * 获取订单数据模型
     * @return StoreOrder
     */
    public function getStoreOrder(): StoreOrder
    {
        return $this->storeOrder;
    }

    /**
     * 设置订单数据模型
     * @param StoreOrder $storeOrder
     * @return HasStoreOrder|static
     */
    public function setStoreOrder(StoreOrder $storeOrder): self
    {
        if ($storeOrder->isEmpty()) {
            throw new InvalidArgumentException('订单数据模型不能为空');
        }

        $this->storeOrder = $storeOrder;
        return $this;
    }
}
