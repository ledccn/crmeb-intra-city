<?php

namespace Ledc\CrmebIntraCity;

use InvalidArgumentException;
use Ledc\DeliverySlotBooking\Helper;

/**
 * 订单表扩展字段
 */
class StoreOrderDevelop
{
    /**
     * 约定的送达时间
     * @var int
     */
    public int $expected_finished_time = 0;
    /**
     * 约定送达的开始时间
     * @var string
     */
    public string $expected_finished_start_time = '';
    /**
     * 约定送达的结束时间
     * @var string
     */
    public string $expected_finished_end_time = '';
    /**
     * 约定的送礼人手机
     * @var string
     */
    public string $owner_phone = '';
    /**
     * 贺卡内容
     * @var string
     */
    public string $greeting = '';

    /**
     * 构造函数
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                $this->{$key} = $value;
            }
        }
        $this->verifyOwnerAppointTime();
    }

    /**
     * 约定的送达时间默认值
     * @return int
     */
    public static function defaultOwnerAppointTime(): int
    {
        return Helper::appointmentTimestamp();
    }

    /**
     * 验证送达时间
     * @return bool
     */
    public function verifyOwnerAppointTime(): bool
    {
        if (empty($this->expected_finished_time)) {
            throw new InvalidArgumentException('预期送达时间不能为空');
        }

        if ($this->expected_finished_time < Helper::appointmentTimestamp()) {
            throw new InvalidArgumentException('预期送达时间必须为' . Helper::config()->getPreparationTime() . '分钟之后');
        }

        return true;
    }
}
