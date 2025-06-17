<?php

namespace Ledc\CrmebIntraCity\enums;

/**
 * 订单状态变更类型枚举
 */
class OrderChangeTypeEnums
{
    /**
     * 创建同城配送运力单
     */
    public const CITY_CREATE_ORDER = 'city_create_order';
    /**
     * 同城配送订单状态回调
     */
    public const CITY_NOTIFY_CALLBACK  = 'city_notify_callback';
    /**
     * 订单变更类型：变更地址
     */
    public const CHANGE_ADDRESS = 'change_address';
    /**
     * 订单变更类型：变更期望送达时间
     */
    public const CHANGE_EXPECTED_FINISHED_TIME = 'change_expected_finished_time';
}
