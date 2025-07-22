<?php

namespace Ledc\CrmebIntraCity\parameters;

use InvalidArgumentException;
use JsonSerializable;

/**
 * 地址解析（地址转坐标）
 * - 本接口提供由文字地址到经纬度的转换能力，并同时提供结构化的省市区地址信息。
 */
class Address2LocationParameters implements JsonSerializable
{
    /**
     * 地址解析（地址转坐标）
     * @link https://lbs.qq.com/service/webService/webServiceGuide/address/Geocoder
     */
    public const BASE_URL = Location2AddressParameters::BASE_URL;
    /**
     * 腾讯地图KEY
     * @var string
     */
    public string $key;
    /**
     * 要解析获取坐标及相关信息的 输入地址，参数要求：
     * - 1. 为提升解析准确率，地址中请至少包含城市名称，否则将视为参数错误，同时地址请尽量完整、具体（包括省市区乡镇/街道门牌及详细地点信息）
     * - 2. 需要对地址进行URL编码，否则若包含"#"等一些功能字符将引起错误
     * @var string
     */
    public string $address;
    /**
     * 解析策略：
     * - 0 [默认] 标准，为保证准确，地址中须包含城市
     * - 1 宽松，允许地址中缺失城市，因各城市同名地点较多，准确性会受一定影响
     * @var int
     */
    public int $policy = 0;
    /**
     * 返回格式：支持JSON/JSONP，默认JSON
     * @var string
     */
    public string $output = 'json';
    /**
     * JSONP方式回调函数
     * @var string
     */
    public string $callback = '';

    /**
     * @param string $key 腾讯地图KEY
     * @param string $address 地址信息
     */
    public function __construct(string $key, string $address)
    {
        if (empty($key)) {
            throw new InvalidArgumentException('腾讯地图KEY不能为空');
        }
        if (empty($address)) {
            throw new InvalidArgumentException('地址信息不能为空');
        }
        $this->key = $key;
        $this->address = $address;
    }

    /**
     * 转数组
     * @return array
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * 转数组
     * @return array
     */
    public function jsonSerialize(): array
    {
        return array_filter(get_object_vars($this), fn($value) => !is_null($value) && '' !== $value && [] !== $value);
    }
}
