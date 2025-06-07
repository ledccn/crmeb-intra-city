<?php

namespace Ledc\CrmebIntraCity\model;

use think\Model;

/**
 * 同城配送加价策略表
 * @property integer $id (主键)
 * @property string $strategy_name 策略名称
 * @property integer $minutes_until_increase X分钟未接单开始加价
 * @property integer $increase_interval_minutes 每隔X分钟加价一次
 * @property integer $increase_amount 每次加价金额：分
 * @property integer $max_increase_amount 加价上限金额：分
 * @property integer|bool $is_active 是否启用
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class EbPricingIncreaseStrategy extends Model
{
    /**
     * The table associated with the model.
     * @var string
     */
    protected $table = 'eb_pricing_increase_strategy';

    /**
     * The primary key associated with the table.
     * @var string
     */
    protected $pk = 'id';
}
