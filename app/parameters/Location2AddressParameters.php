<?php

namespace Ledc\CrmebIntraCity\parameters;

use InvalidArgumentException;
use JsonSerializable;

/**
 * 地理位置转地址
 * - 逆地址解析（坐标位置描述）
 * @link https://lbs.qq.com/service/webService/webServiceGuide/address/Gcoder
 */
class Location2AddressParameters implements JsonSerializable
{
    /**
     * 逆地址解析（坐标位置描述）
     * @link https://lbs.qq.com/service/webService/webServiceGuide/address/Gcoder
     */
    public const BASE_URL = 'https://apis.map.qq.com/ws/geocoder/v1/';
    /**
     * 腾讯地图KEY
     * @var string
     */
    public string $key;
    /**
     * 经纬度（GCJ02坐标系），格式：location=lat<纬度>,lng<经度>
     * - 示例：location=23.137466,113.352425
     * @var string
     */
    public string $location;
    /**
     * 是否返回周边POI列表
     * - 0：不返回周边POI列表
     * - 1：返回周边POI列表
     * @var int
     */
    public int $get_poi = 0;
    /**
     * 周边POI列表参数
     * @var array|null
     */
    public ?array $poi_options = null;
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
     * @param string $lat 纬度
     * @param string $lng 经度
     */
    public function __construct(string $key, string $lat, string $lng)
    {
        if (empty($key)) {
            throw new InvalidArgumentException('腾讯地图KEY不能为空');
        }
        if (empty($lat) || empty($lng)) {
            throw new InvalidArgumentException('经纬度不能为空');
        }
        $this->key = $key;
        $this->location = $lat . ',' . $lng;
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
