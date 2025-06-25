<?php

namespace Ledc\CrmebIntraCity;

use crmeb\services\SystemConfigService;
use Ledc\ShanSong\Config;
use Ledc\ShanSong\Merchant;
use think\App;

/**
 * 闪送助手
 */
class ShanSongHelper
{
    /**
     * 闪送门店ID
     */
    public const SHANSONG_STORE_ID = 'shansong_store_id';

    /**
     * 闪送是否启用
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return sys_config(Config::CONFIG_PREFIX . 'enabled', false);
    }

    /**
     * 获取商家承担的运费金额
     * @return float
     */
    public static function getSellerFreightLimit(): float
    {
        return sys_config(Config::CONFIG_PREFIX . 'seller_freight_limit', 10);
    }

    /**
     * 获取配置
     * @return array
     */
    public static function getConfig(): array
    {
        // 从数据库取配置
        $result = SystemConfigService::more(array_map(fn($key) => Config::CONFIG_PREFIX . $key, Config::REQUIRE_KEYS), false);

        // 移除配置前缀
        $keys = array_map(fn($key) => substr($key, strlen(Config::CONFIG_PREFIX)), array_keys($result));

        return array_combine($keys, array_values($result));
    }

    /**
     * 获取商户对象
     * @return Merchant
     */
    public static function merchant(): Merchant
    {
        /** @var App $app */
        $app = app();
        if ($app->exists(Merchant::class)) {
            return $app->make(Merchant::class);
        }

        $systemConfig = self::getConfig();

        // 实例化
        $config = new Config($systemConfig);
        $merchant = new Merchant($config);

        // 绑定类实例到容器
        $app->instance(Config::class, $config);
        $app->instance(Merchant::class, $merchant);

        return $merchant;
    }
}
