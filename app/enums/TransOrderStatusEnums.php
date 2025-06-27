<?php

namespace Ledc\CrmebIntraCity\enums;

/**
 * 同城配送单状态（所有运力共用）
 */
class TransOrderStatusEnums
{
    /**
     * 默认
     */
    const DEFAULT = 0;
    /**
     * 派单中
     */
    const Assigned = 1;
    /**
     * 待取货
     */
    const PendingPickup = 2;
    /**
     * 送货中
     */
    const InTransit = 3;
    /**
     * 已完成
     */
    const Completed = 4;
    /**
     * 已取消
     */
    const Cancelled = 5;

    /**
     * 获取订单状态枚举值
     * @return string[]
     */
    public static function cases(): array
    {
        return [
            self::DEFAULT => '默认',
            self::Assigned => '派单中',
            self::PendingPickup => '待取货',
            self::InTransit => '送货中',
            self::Completed => '已完成',
            self::Cancelled => '已取消',
        ];
    }

    /**
     * 获取带计数的枚举列表
     * @param array $counts 每个状态的计数数组，键为状态码
     * @return array
     */
    public static function listWithCounts(array $counts): array
    {
        $cases = self::cases();
        $result = [];

        foreach ($cases as $value => $name) {
            $result[] = [
                'value' => $value,
                'name' => $name,
                'count' => $counts[$value] ?? 0,
            ];
        }

        return $result;
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

    /**
     * 允许变更订单的收货地址或期望送达时间
     * @param int $value
     * @return bool
     */
    public static function isAllowChangeAddressOrExpectedFinishedTime(int $value): bool
    {
        return in_array($value, [
            self::DEFAULT,
            self::Cancelled,
        ], true);
    }
}
