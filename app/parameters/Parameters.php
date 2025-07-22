<?php

namespace Ledc\CrmebIntraCity\parameters;

use JsonSerializable;
use Ledc\ThinkModelTrait\Contracts\HasExists;
use Ledc\ThinkModelTrait\Contracts\HasJsonSerializable;

/**
 * 抽象参数类
 */
abstract class Parameters implements JsonSerializable
{
    use HasJsonSerializable, HasExists, HasCache;

    /**
     * 内部订单号
     * @var string
     */
    private string $order_id;

    /**
     * 获取内部订单号
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->order_id;
    }

    /**
     * 设置内部订单号
     * @param string $order_id
     * @return self
     */
    public function setOrderId(string $order_id): self
    {
        $this->order_id = $order_id;
        return $this;
    }

    /**
     * 获取排除的属性
     * @return string[]
     */
    protected function getExcludesKeys(): array
    {
        return ['exists', 'order_id'];
    }

    /**
     * 设置缓存参数
     * @return self
     */
    public function cache(): self
    {
        $this->setCache($this->jsonSerialize());
        return $this;
    }
}
