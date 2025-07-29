<?php

use crmeb\utils\Json;
use Ledc\CrmebIntraCity\WechatIntraCityHelper;
use Ledc\IntraCity\ExpressApi;
use Ledc\ThinkModelTrait\RedisUtils;

if (!function_exists('wechat_express_api')) {
    /**
     * 微信配送API
     * @return ExpressApi
     */
    function wechat_express_api(): ExpressApi
    {
        return WechatIntraCityHelper::api();
    }
}

if (!function_exists('response_json')) {
    /**
     * JSON响应
     * @return Json
     */
    function response_json(): Json
    {
        return app('json');
    }
}

if (!function_exists('generate_order_seq')) {
    /**
     * 生成订单流水号
     * @return int
     */
    function generate_order_seq(): int
    {
        return RedisUtils::incr('wechat_order_seq:' . date('Ymd'), 86400 * 2);
    }
}

if (!function_exists('get_order_seq')) {
    /**
     * 获取订单流水号
     * @param int $paidTime 订单的支付时间（秒时间戳）
     * @param int $orderSeq 订单流水号
     * @param int $length 订单流水号长度（不足时在左侧补0）
     * @return string
     */
    function get_order_seq(int $paidTime, int $orderSeq, int $length = 4): string
    {
        return date('md', $paidTime) . str_pad($orderSeq, $length, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('generate_order_number')) {
    /**
     * 生成20位纯数字订单号
     * - 规则：年月日时分秒 + 6位微秒数（示例值20241101235959123456）
     * @return string
     */
    function generate_order_number(): string
    {
        [$mSec, $timestamp] = explode(' ', microtime());
        return date('YmdHis', (int)$timestamp) . substr($mSec, 2, 6);
    }
}

if (!function_exists('generate_order_sn')) {
    /**
     * 生成18位纯数字订单号
     * - 规则：年月日时分秒 + 4位微秒数（示例值202411012359591234）
     * @return string
     */
    function generate_order_sn(): string
    {
        [$timestamp, $mSec] = explode('.', microtime(true));
        return date('YmdHis', (int)$timestamp) . str_pad($mSec, 4, '0');
    }
}

/**
 * 获取类属性及其注释
 */
function getClassPropertiesWithComments(string $className): array
{
    if (!class_exists($className)) {
        return [];
    }

    $properties = [];
    $reflection = new ReflectionClass($className);
    foreach ($reflection->getProperties() as $property) {
        $properties[$property->getName()] = getDocCommentSummary($property);
    }

    return $properties;
}

/**
 * 提取属性注释摘要
 */
function getDocCommentSummary(ReflectionProperty $property): ?string
{
    $docComment = $property->getDocComment();
    if (!$docComment) {
        return null;
    }

    $docComment = str_replace("\r\n", "\n", $docComment);
    $lines = explode("\n", trim(substr($docComment, 3, -2)));
    $summary = '';

    foreach ($lines as $line) {
        $line = ltrim($line, ' *');
        if (!empty($line)) {
            $summary .= $line . ' ';
        }
    }

    return trim($summary);
}
