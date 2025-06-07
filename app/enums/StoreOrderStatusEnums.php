<?php

namespace Ledc\CrmebIntraCity\enums;

/**
 * 订单状态枚举
 */
class StoreOrderStatusEnums
{
    /**
     * 订单申请退款中
     */
    public const REFUND_APPLIED = -1;
    /**
     * 退货成功
     */
    public const RETURN_COMPLETED = -2;
    /**
     * 待发货
     * - 待发货、待核销、拼团中
     */
    public const PENDING = 0;
    /**
     * 已发货
     * - 已发货待收货
     */
    public const SHIPPED = 1;
    /**
     * 已收货
     * - 已收货待评价
     */
    public const RECEIVED = 2;
    /**
     * 交易完成
     * - 已评价交易完成
     */
    public const COMPLETED = 3;

    /**
     * 枚举说明列表
     * @return string[]
     */
    public static function cases(): array
    {
        return [
            self::REFUND_APPLIED => '申请退款中',
            self::RETURN_COMPLETED => '退货成功',
            self::PENDING => '待发货',
            self::SHIPPED => '待收货',
            self::RECEIVED => '已收货',
            self::COMPLETED => '交易完成',
        ];
    }

    /**
     * 验证枚举值是否有效
     * @param int $value
     * @return bool
     */
    public static function isValid(int $value): bool
    {
        return array_key_exists($value, self::cases());
    }

    /**
     * 枚举列表
     * @return string[]
     */
    public static function list(): array
    {
        $rs = [];
        foreach (self::cases() as $value => $name) {
            $rs[] = compact('name', 'value');
        }
        return $rs;
    }
}
