<?php

namespace Ledc\CrmebIntraCity;

/**
 * 同城配送运力ID枚举
 */
class ServiceTransEnums
{
    /**
     * 闪送
     */
    public const TRANS_SHANSONG = 'SHANSONG';
    /**
     * 顺丰同城
     */
    public const TRANS_SFTC = 'SFTC';
    /**
     * 达达
     */
    public const TRANS_DADA = 'DADA';

    /**
     * 获取同城配送运力ID列表
     * @return string[]
     */
    public static function cases(): array
    {
        return [
            self::TRANS_SHANSONG => '闪送',
            self::TRANS_SFTC => '顺丰同城',
            self::TRANS_DADA => '达达',
        ];
    }

    /**
     * 获取带状态的枚举列表
     * @return array
     */
    public static function status(): array
    {
        return [
            self::TRANS_SHANSONG => ShanSongHelper::isEnabled(),
            self::TRANS_SFTC => WechatIntraCityHelper::isEnabled(),
            self::TRANS_DADA => WechatIntraCityHelper::isEnabled(),
        ];
    }

    /**
     * 是否启用
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return ShanSongHelper::isEnabled() || WechatIntraCityHelper::isEnabled();
    }

    /**
     * 验证枚举值是否有效
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
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

    /**
     * 获取带状态的枚举列表
     * @return array
     */
    public static function listWithStatus(): array
    {
        $cases = self::cases();
        $status = self::status();
        $result = [];

        foreach ($cases as $value => $name) {
            $result[] = [
                'value' => $value,
                'name' => $name,
                'enabled' => $status[$value] ?? false,
            ];
        }

        return $result;
    }
}
