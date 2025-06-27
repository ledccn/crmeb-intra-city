<?php

namespace Ledc\CrmebIntraCity\traits;

use Ledc\CrmebIntraCity\StoreOrderDevelop;

/**
 * 请求处理（约定的送达时间）
 */
trait RequestTrait
{
    /**
     * 约定的送达时间
     * @var int
     */
    protected int $expected_finished_time = 0;

    /**
     * 约定的送达时间
     * @param int $value
     * @return $this
     */
    public function setOwnerAppointTime(int $value): self
    {
        $this->expected_finished_time = $value;
        return $this;
    }

    /**
     * 获取约定送达时间
     * @return int
     */
    public function getOwnerAppointTime(): int
    {
        return $this->expected_finished_time ?: StoreOrderDevelop::defaultOwnerAppointTime();
    }
}
