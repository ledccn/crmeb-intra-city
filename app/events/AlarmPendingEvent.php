<?php

namespace Ledc\CrmebIntraCity\events;

use think\facade\Cache;

/**
 * 订单预警事件
 * - 即将超时的待发货订单（包含待发单、派单中、待取货）
 */
class AlarmPendingEvent
{
    /**
     * 订单hash值
     * @var string
     */
    protected string $hash_value;

    /**
     * 构造函数
     * @param string $hash_value
     */
    public function __construct(string $hash_value)
    {
        $this->hash_value = $hash_value;
    }

    /**
     * 获取订单hash值
     * @return string
     */
    public function getHashValue(): string
    {
        return $this->hash_value;
    }

    /**
     * 判断缓存是否存在
     * @return bool
     */
    public function hasCache(): bool
    {
        return Cache::has($this->getCacheKey());
    }

    /**
     * 获取缓存
     * @return int
     */
    public function getCache(): int
    {
        return Cache::get($this->getCacheKey(), 0);
    }

    /**
     * 设置缓存
     * @return self
     */
    public function setCache(): self
    {
        Cache::set($this->getCacheKey(), time(), $this->getCacheExpire());
        return $this;
    }

    /**
     * 获取缓存key
     * @return string
     */
    public function getCacheKey(): string
    {
        return 'AlarmPending_' . $this->hash_value;
    }

    /**
     * 获取缓存过期时间
     * @return int
     */
    public function getCacheExpire(): int
    {
        return 1800;
    }
}
