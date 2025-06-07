<?php

namespace Ledc\CrmebIntraCity;

use crmeb\services\app\MiniProgramService;
use crmeb\services\SystemConfigService;
use EasyWeChat\Core\AccessToken;
use Ledc\IntraCity\Config;
use Ledc\IntraCity\ExpressApi;
use think\App;

/**
 * 微信同城配送助手类
 */
class WechatIntraCityHelper
{
    /**
     * 微信同城配送是否启用
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return sys_config('wechat_enable', false);
    }

    /**
     * 获取微信同城配送配置
     * @return array
     */
    public static function getConfig(): array
    {
        // 小程序APP实例
        $wechatApp = MiniProgramService::application();
        /** @var AccessToken $miniProgramAccessToken */
        $miniProgramAccessToken = $wechatApp['mini_program.access_token'];

        // 从数据库取配置
        $system_config = SystemConfigService::more([
            'wechat_aes_sn',
            'wechat_aes_key',
            'wechat_rsa_sn',
            'wechat_rsa_public_key',
            'wechat_rsa_private_key',
            'wechat_cert_sn',
            'wechat_cert_key',
            'wechat_callback_url',
            'wechat_wx_store_id',
            'wechat_order_detail_path',
            'wechat_enable',
            'wechat_use_sandbox',
        ]);

        // 移除配置前缀
        $keys = array_map(fn($key) => substr($key, strlen('wechat_')), array_keys($system_config));

        $config = array_combine($keys, array_values($system_config));

        // 补充其余配置
        $config['appid'] = $miniProgramAccessToken->getAppId();
        $config['secret'] = $miniProgramAccessToken->getSecret();
        $config['token'] = $wechatApp['config']['token'];
        $config['access_token'] = function (string $appid) use ($miniProgramAccessToken) {
            return $miniProgramAccessToken->getToken();
        };

        return $config;
    }

    /**
     * 微信配送API
     * @return ExpressApi
     */
    public static function api(): ExpressApi
    {
        /** @var App $app */
        $app = app();
        if ($app->exists(ExpressApi::class)) {
            return $app->make(ExpressApi::class);
        } else {
            $attributes = self::getConfig();

            // 实例化
            $config = new Config($attributes);
            $expressApi = new ExpressApi($config);

            // 绑定类实例到容器
            $app->instance(Config::class, $config);
            $app->instance(ExpressApi::class, $expressApi);

            return $expressApi;
        }
    }
}
