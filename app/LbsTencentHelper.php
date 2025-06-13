<?php

namespace Ledc\CrmebIntraCity;

/**
 * 腾讯地图助手类
 * @link https://lbs.qq.com/faq/serverFaq/webServiceKey
 *
 */
final class LbsTencentHelper
{
    /**
     * WebServiceAPI：腾讯地图KEY 授权域名
     */
    public const LBS_TENCENT_KEY_DOMAIN = 'lbs_qq_map_key_domain';
    /**
     * WebServiceAPI：腾讯地图KEY 授权 IP 地址
     */
    public const LBS_TENCENT_KEY_IP = 'lbs_qq_map_key_server';
    /**
     * WebServiceAPI：腾讯地图KEY 签名校验
     */
    public const LBS_TENCENT_KEY_SIGNATURE = 'lbs_qq_map_key_signature';
    /**
     * WebServiceAPI：腾讯地图KEY 签名校验 Secret key（ SK ）
     * - 通过签名校验的方式，可有效解决请求伪造，稍有开发量，但与授权IP方式比较，不必担心服务器换IP的问题。
     * @link https://lbs.qq.com/faq/serverFaq/webServiceKey
     */
    public const LBS_TENCENT_KEY_SECRET = 'lbs_qq_map_key_secret_key';
    /**
     * 微信小程序：腾讯地图KEY 授权APPID
     */
    public const LBS_TENCENT_KEY_APPID = 'lbs_qq_map_key_appid';

    /**
     * WebServiceAPI：腾讯地图KEY 授权域名
     * @return string|null
     */
    public static function getDomainKey(): ?string
    {
        return sys_config(self::LBS_TENCENT_KEY_DOMAIN, null);
    }

    /**
     * WebServiceAPI：腾讯地图KEY 授权IP 地址
     * @return string|null
     */
    public static function getIpKey(): ?string
    {
        return sys_config(self::LBS_TENCENT_KEY_IP, null);
    }

    /**
     * WebServiceAPI：腾讯地图KEY 签名校验
     * @return string|null
     */
    public static function getSignatureKey(): ?string
    {
        return sys_config(self::LBS_TENCENT_KEY_SIGNATURE, null);
    }

    /**
     * WebServiceAPI：腾讯地图KEY 签名校验 Secret key（ SK ）
     * @return string|null
     */
    public static function getSignatureSecretKey(): ?string
    {
        return sys_config(self::LBS_TENCENT_KEY_SECRET, null);
    }

    /**
     * 微信小程序：腾讯地图KEY 授权APPID
     * @return string|null
     */
    public static function getAppIdKey(): ?string
    {
        return sys_config(self::LBS_TENCENT_KEY_APPID, null);
    }
}
