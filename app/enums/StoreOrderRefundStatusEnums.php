<?php

namespace Ledc\CrmebIntraCity\enums;

/**
 * 订单退款状态枚举
 */
class StoreOrderRefundStatusEnums
{
    /**
     * 未退款
     */
    public const NOT_REFUNDED = 0;
    /**
     * 申请退款中
     */
    public const REFUNDING = 1;
    /**
     * 已退款
     */
    public const REFUNDED = 2;
    /**
     * 子订单部分退款
     */
    public const PARTIAL_REFUND = 3;
    /**
     * 子订单已全部申请退款中
     */
    public const FULL_REFUND_APPLIED = 4;

    /**
     * 枚举说明列表
     * @return string[]
     */
    public static function cases(): array
    {
        return [
            self::NOT_REFUNDED => '未退款',
            self::REFUNDING => '申请退款中',
            self::REFUNDED => '已退款',
            self::PARTIAL_REFUND => '子订单部分退款',
            self::FULL_REFUND_APPLIED => '子订单已全部申请退款中',
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
     * @return array
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
