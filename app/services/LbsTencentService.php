<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\shipping\SystemCity;
use Ledc\CrmebIntraCity\parameters\Location2AddressParameters;
use Ledc\ThinkModelTrait\Contracts\Curl;
use RuntimeException;
use function json_encode;

/**
 * 腾讯地图服务
 */
class LbsTencentService
{
    /**
     * 经纬度转地址
     * - 逆地址解析（坐标位置描述）
     * - 本接口提供由经纬度到文字地址及相关位置信息的转换能力，广泛应用于物流、出行、O2O、社交等场景。服务响应速度快、稳定，支撑亿级调用。支持根据输入经纬度，获取：
     * - 1. 经纬度所在省、市、区、乡镇、门牌号、行政区划代码，及周边参考位置信息，如道路及交叉口、河流、湖泊、桥等
     * - 2. 通过知名地点、地标组合形成的易于理解的地址，如：北京市海淀区中钢国际广场(欧美汇购物中心北)。
     * - 3. 商圈、附近知名的一级地标、代表当前位置的二级地标等。
     * - 4. 周边POI（AOI）列表。
     * @link https://lbs.qq.com/service/webService/webServiceGuide/address/Gcoder
     * @param Location2AddressParameters $parameters
     * @return array
     */
    public static function location2address(Location2AddressParameters $parameters): array
    {
        $curl = new Curl();
        $curl->setTimeout()->setSslVerify();
        $curl->setReferer(sys_config('site_url'));
        $curl->get(Location2AddressParameters::BASE_URL, $parameters->toArray());

        return self::parseHttpResponse($curl);
    }

    /**
     * 获取系统城市
     * @param string $area_code 行政区划代码 https://lbs.qq.com/service/webService/webServiceGuide/search/webServiceDistrict#6
     * @return SystemCity
     */
    public static function getSystemCity(string $area_code): SystemCity
    {
        $city = SystemCity::where('area_code', 'like', $area_code . '%')->findOrEmpty();
        if ($city->level == 2) {
            $city = $city->where('city_id', $city->parent_id)->findOrEmpty();
        }
        if ($city->isEmpty()) {
            throw new RuntimeException('未找到该城市' . $area_code);
        }
        return $city;
    }

    /**
     * 解析HTTP响应
     * @param Curl $httpResponse
     * @return array
     */
    final protected static function parseHttpResponse(Curl $httpResponse): array
    {
        $response = $httpResponse->getResponse();
        if (!$httpResponse->isSuccess()) {
            $code = $httpResponse->getErrorCode();
            $msg = $httpResponse->getErrorMessage();
            throw new RuntimeException('CURL请求腾讯经纬度转地址错误：' . json_encode(compact('code', 'msg', 'response'), JSON_UNESCAPED_UNICODE));
        }

        $response = json_decode($httpResponse->getResponse(), true);
        // 状态码，0为正常，其它为异常
        $status = $response['status'] ?? 0;
        // 状态说明
        $message = $response['message'] ?? '';
        if (0 === $status) {
            return $response['result'];
        }

        throw new RuntimeException('逆地址解析失败：' . json_encode(compact('status', 'message'), JSON_UNESCAPED_UNICODE));
    }
}
