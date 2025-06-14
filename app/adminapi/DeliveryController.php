<?php

namespace Ledc\CrmebIntraCity\adminapi;

use app\Request;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\locker\OrderLocker;
use Ledc\CrmebIntraCity\services\DeliveryServices;
use Ledc\CrmebIntraCity\ServiceTransEnums;
use think\Response;

/**
 * 配送管理
 */
class DeliveryController
{
    /**
     * 同城配送运力ID枚举
     * @method GET
     * @return Response
     */
    public function trans(): Response
    {
        return response_json()->success('ok', ServiceTransEnums::cases());
    }

    /**
     * 同城配送运力单状态（所有运力共用）
     * @return Response
     */
    public function status(): Response
    {
        return response_json()->success('ok', TransOrderStatusEnums::list());
    }

    /**
     * 创建配送单
     * - 呼叫骑手
     * @method POST
     * @param Request $request
     * @return Response
     */
    public function create(Request $request): Response
    {
        [$id, $service_trans_id, $params] = $request->postMore([
            'id/d',
            ['service_trans_id/s', ServiceTransEnums::TRANS_SHANSONG],
            ['params/a', []],
        ], true);

        $locker = OrderLocker::create($id);
        if (!$locker->acquire()) {
            return response_json()->fail('未获取到锁，请稍后再试');
        }

        return response_json()->success(100010, DeliveryServices::create($id, $service_trans_id, $params));
    }
}
