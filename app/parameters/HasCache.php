<?php

namespace Ledc\CrmebIntraCity\parameters;

use think\facade\Cache;

/**
 * 特征：获取缓存、设置缓存
 */
trait HasCache
{
    /**
     * 获取缓存key
     * @return string
     */
    abstract public function getCacheKey(): string;

    /**
     * 获取缓存过期时间
     * @return int
     */
    protected function getCacheExpire(): int
    {
        return 86400 * 10;
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
     * @return array
     */
    public function getCache(): array
    {
        return Cache::get($this->getCacheKey(), []);
    }

    /**
     * 设置缓存
     * @param array $data
     * @return self
     */
    public function setCache(array $data): self
    {
        Cache::set($this->getCacheKey(), $data, $this->getCacheExpire());
        return $this;
    }
}