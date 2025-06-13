<?php

namespace Ledc\CrmebIntraCity\api;

use app\Request;
use Ledc\CrmebIntraCity\LbsTencentHelper;
use Ledc\CrmebIntraCity\parameters\Location2AddressParameters;
use Ledc\CrmebIntraCity\services\LbsTencentService;
use think\Response;

/**
 * 腾讯地图
 */
class TencentMapController
{
    /**
     * 经纬度转地址
     * - 逆地址解析（坐标位置描述）
     * @link https://lbs.qq.com/service/webService/webServiceGuide/address/Gcoder
     * @param Request $request
     * @return Response
     */
    public function location2address(Request $request): Response
    {
        $latitude = $request->param('latitude');
        $longitude = $request->param('longitude');
        $parameters = new Location2AddressParameters(
            LbsTencentHelper::getAppIdKey(),
            $latitude,
            $longitude
        );

        $result = LbsTencentService::location2address($parameters);
        $ad_code = $result['ad_info']['adcode'];
        $city = LbsTencentService::getSystemCity($ad_code);
        $result['city_id'] = $city->city_id;

        return response_json()->success('ok', $result);
    }
}
