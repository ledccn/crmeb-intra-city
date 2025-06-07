<?php

namespace Ledc\CrmebIntraCity\validate;

use think\Validate;

/**
 * 同城配送加价策略表验证器
 */
class PricingIncreaseStrategyValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'strategy_name' => 'require',
        'minutes_until_increase' => 'require|number',
        'increase_interval_minutes' => 'require|number',
        'increase_amount' => 'require|number',
        'max_increase_amount' => 'require|number',
        'is_active' => 'require|bool',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'strategy_name.require' => '策略名称不能为空',
        'minutes_until_increase.require' => 'X分钟未接单开始加价不能为空',
        'minutes_until_increase.number' => 'X分钟未接单开始加价必须为数字',
        'increase_interval_minutes.require' => '每隔X分钟加价一次不能为空',
        'increase_interval_minutes.number' => '每隔X分钟加价一次必须为数字',
        'increase_amount.require' => '每次加价金额不能为空',
        'increase_amount.number' => '每次加价金额必须为数字',
        'max_increase_amount.require' => '加价上限金额不能为空',
        'max_increase_amount.number' => '加价上限金额必须为数字',
        'is_active.require' => '是否启用不能为空',
        'is_active.bool' => '是否启用必须为布尔值',
    ];
}
