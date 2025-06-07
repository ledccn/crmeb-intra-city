<?php

namespace Ledc\CrmebIntraCity\api;

use app\Request;
use Ledc\CrmebIntraCity\services\WechatIntraCityService;
use Ledc\IntraCity\Contracts\CallableNotify;
use think\facade\Log;
use think\Response;
use Throwable;

/**
 * 微信同城配送
 */
class WechatController
{
    /**
     * 订单状态回调
     * @param Request $request
     * @return Response
     */
    public function notifyCallback(Request $request): Response
    {
        $crmeb = new WechatIntraCityService();
        $req = $request->post(false);
        Log::record('微信同城配送回调：' . json_encode($req, JSON_UNESCAPED_UNICODE));
        try {
            $notify = new CallableNotify($req, $crmeb->getApi()->getConfig()->getToken());
            $crmeb->notifyCallback($notify);
            return Response::create($notify->response(), 'json');
        } catch (Throwable $th) {
            Log::error('微信同城配送回调异常：' . json_encode($req, JSON_UNESCAPED_UNICODE));
            return response($th->getMessage(), 400);
        }
    }
}
