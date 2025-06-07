<?php

use crmeb\utils\Json;
use Ledc\CrmebIntraCity\WechatIntraCityHelper;
use Ledc\IntraCity\ExpressApi;

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
