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
    use HasJsonSerializable, HasExists, HasStoreOrder, HasCache;

    /**
     * 获取排除的属性
     * @return string[]
     */
    protected function getExcludesKeys(): array
    {
        return ['exists', 'storeOrder'];
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
