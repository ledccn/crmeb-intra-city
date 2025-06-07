<?php

namespace Ledc\CrmebIntraCity\enums;

/**
 * 订单支付状态枚举
 */
class StoreOrderPaidEnums
{
    /**
     * 未支付
     */
    public const UNPAID = 0;
    /**
     * 已支付
     */
    public const PAID = 1;

    /**
     * 枚举说明列表
     * @return string[]
     */
    public static function cases(): array
    {
        return [
            self::UNPAID => '未支付',
            self::PAID => '已支付',
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
