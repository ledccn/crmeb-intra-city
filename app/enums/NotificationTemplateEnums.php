<?php

namespace Ledc\CrmebIntraCity\enums;

/**
 * 订单异常通知模版枚举类
 */
class NotificationTemplateEnums
{
    /**
     * 待发货超时提醒客服
     */
    public const ADMIN_ORDER_TIMEOUT_EXCEPTION = 'admin_order_timeout_exception';
    /**
     * 异常订单处理通知客服
     */
    public const ADMIN_ORDER_EXCEPTION = 'admin_order_exception';
    /**
     * 配送订单审核通知客服
     */
    public const ADMIN_ORDER_AUDIT = 'admin_order_audit';
}
