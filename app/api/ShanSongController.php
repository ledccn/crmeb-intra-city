<?php

namespace Ledc\CrmebIntraCity\api;

use app\model\order\StoreOrder;
use app\Request;
use Ledc\CrmebIntraCity\services\ShanSongService;
use Ledc\ShanSong\Parameters\Notify;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\facade\Log;
use think\Response;
use Throwable;
use function request;

/**
 * 闪送
 */
class ShanSongController
{
    /**
     * @var ShanSongService
     */
    protected ShanSongService $services;

    /**
     * 构造函数
     * @param ShanSongService $service
     */
    public function __construct(ShanSongService $service)
    {
        $this->services = $service;
    }

    /**
     * 订单状态回调
     * @param Request $request
     * @return Response
     */
    public function notifyCallback(Request $request): Response
    {
        $uniqid = $request->secureKey();
        log_develop($uniqid . ' 闪送 订单状态回调：' . json_encode($request->post(false), JSON_UNESCAPED_UNICODE));
        try {
            $notify = new Notify($request->post(false));
            $status = $this->services->notifyCallback($notify);
            $msg = '';
        } catch (Throwable $throwable) {
            Log::error($uniqid . ' 闪送 订单状态回调异常：' . $throwable->getMessage());
            $status = 400;
            $msg = $throwable->getMessage();
        }

        return Response::create(['status' => $status, 'msg' => $msg, 'data' => null], 'json');
    }

    /**
     * 获取订单数据模型
     * @param int $id
     * @return StoreOrder
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    protected function getStoreOrder(int $id): StoreOrder
    {
        /** @var StoreOrder $order */
        $order = (new StoreOrder)->db()
            ->where('id', $id)
            ->where('uid', request()->uid())
            ->findOrFail();
        return $order;
    }

    /**
     * 查询订单详情
     * @param int $id
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function orderInfo(int $id): Response
    {
        $storeOrder = $this->getStoreOrder($id);
        $result = $this->services->orderInfo($storeOrder->wechat_trans_order_id, $storeOrder->order_id);
        return response_json()->success('ok', $result);
    }

    /**
     * 查询闪送员位置信息
     * @param int $id
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function courierInfo(int $id): Response
    {
        $storeOrder = $this->getStoreOrder($id);
        $result = $this->services->courierInfo($storeOrder->wechat_trans_order_id);
        return response_json()->success('ok', $result);
    }
}