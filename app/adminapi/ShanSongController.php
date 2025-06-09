<?php

namespace Ledc\CrmebIntraCity\adminapi;

use app\model\order\StoreOrder;
use app\Request;
use Ledc\CrmebIntraCity\locker\OrderLocker;
use Ledc\CrmebIntraCity\parameters\ShanSongParameters;
use Ledc\CrmebIntraCity\services\ShanSongService;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Response;

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
     * 获取订单数据模型
     * @param int $id
     * @return StoreOrder
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    protected function getStoreOrder(int $id): StoreOrder
    {
        /** @var StoreOrder $order */
        $order = StoreOrder::findOrFail($id);
        return $order;
    }

    /**
     * 查询开通城市
     * @method GET
     * @return Response
     */
    public function openCitiesLists(): Response
    {
        return response_json()->success('ok', $this->services->openCitiesLists());
    }

    /**
     * 分页查询商户店铺
     * @method GET
     * @param Request $request
     * @return Response
     */
    public function queryAllStores(Request $request): Response
    {
        [$page, $limit, $storeName] = $request->getMore([
            ['page/d', 1],
            ['limit/d', 20],
            ['store_name', '']
        ], true);

        return response_json()->success('ok', $this->services->queryAllStores($page, $limit, $storeName));
    }

    /**
     * 查询城市可指定的交通工具
     * @method GET
     * @param int $city_id 城市ID（查询开通城市接口获取，对应id字段）
     * @return Response
     */
    public function optionalTravelWay(int $city_id): Response
    {
        return response_json()->success('ok', $this->services->optionalTravelWay($city_id));
    }

    /**
     * 订单计费
     * @method POST
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function orderCalculate(Request $request): Response
    {
        [$id, $params] = $request->postMore([
            'id/d',
            ['params/a', []],
        ], true);
        $storeOrder = $this->getStoreOrder($id);

        $result = $this->services->orderCalculate(
            $storeOrder,
            ShanSongParameters::make($params)->setStoreOrder($storeOrder)
        );
        return response_json()->success('ok', $result->jsonSerialize());
    }

    /**
     * 提交订单
     * @method POST
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function orderPlace(int $id, Request $request): Response
    {
        $params = $request->post('params/a', []);
        $locker = OrderLocker::create($id);
        if (!$locker->acquire()) {
            return response_json()->fail('未获取到锁，请稍后再试');
        }

        $storeOrder = $this->getStoreOrder($id);

        $shanSongParameters = ShanSongParameters::make($params)->setStoreOrder($storeOrder)->cache();
        $result = $this->services->orderPlace(
            $storeOrder,
            $shanSongParameters
        );
        return response_json()->success('ok', $result->jsonSerialize());
    }

    /**
     * 订单加价
     * @method POST
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function addition(Request $request): Response
    {
        [$id, $additionAmount] = $request->postMore(['id', 'addition_amount'], true);
        return response_json()->success('ok', $this->services->addition($this->getStoreOrder($id), $additionAmount));
    }

    /**
     * 查询订单详情
     * @method GET
     * @param int $id
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function orderInfo(int $id): Response
    {
        $result = $this->services->orderInfo($this->getStoreOrder($id));
        return response_json()->success('ok', $result);
    }

    /**
     * 查询闪送员位置信息
     * @method GET
     * @param int $id
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function courierInfo(int $id): Response
    {
        $result = $this->services->courierInfo($this->getStoreOrder($id));
        return response_json()->success('ok', $result);
    }

    /**
     * 查询订单续重加价金额
     * @method GET
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function calculateOrderAddWeightFee(Request $request): Response
    {
        [$id, $weight] = $request->getMore(['id', 'weight'], true);
        return response_json()->success('ok', $this->services->calculateOrderAddWeightFee($this->getStoreOrder($id), $weight));
    }

    /**
     * 支付订单续重费用
     * @method POST
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function payAddWeightFee(Request $request): Response
    {
        [$id, $pay_amount, $weight] = $request->postMore(['id', 'pay_amount', 'weight'], true);
        $result = $this->services->payAddWeightFee($this->getStoreOrder($id), $pay_amount, $weight);
        return response_json()->success('ok', $result);
    }

    /**
     * 订单预取消
     * @method POST
     * @param int $id
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function preAbortOrder(int $id): Response
    {
        $result = $this->services->preAbortOrder($this->getStoreOrder($id));
        return response_json()->success('ok', $result);
    }

    /**
     * 订单取消
     * @method POST
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function abortOrder(Request $request): Response
    {
        $id = $request->post('id');
        $deductFlag = $request->post('deduct_flag', false);
        $result = $this->services->abortOrder($this->getStoreOrder($id), (bool)$deductFlag);
        return response_json()->success('ok', $result);
    }

    /**
     * 确认物品送回
     * @method POST
     * @param int $id
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function confirmGoodsReturn(int $id): Response
    {
        return response_json()->success('ok', $this->services->confirmGoodsReturn($this->getStoreOrder($id)));
    }

    /**
     * 查询账号额度
     * @method GET
     * @return Response
     */
    public function getUserAccount(): Response
    {
        return response_json()->success('ok', $this->services->getUserAccount());
    }

    /**
     * 修改收件人手机号
     * @method POST
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function updateToMobile(Request $request): Response
    {
        [$id, $newToMobile] = $request->postMore(['id', 'new_to_mobile'], true);
        return response_json()->success('ok', $this->services->updateToMobile($this->getStoreOrder($id), $newToMobile));
    }

    /**
     * 查询是否支持尊享送
     * @method GET
     * @param Request $request
     * @return Response
     */
    public function qualityDeliverySwitch(Request $request): Response
    {
        $cityName = $request->get('city_name');
        return response_json()->success('ok', $this->services->qualityDeliverySwitch($cityName));
    }

    /**
     * 查询尊享送达成状态
     * @method GET
     * @param int $id
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function qualityDeliveryStatus(int $id): Response
    {
        return response_json()->success('ok', $this->services->qualityDeliveryStatus($this->getStoreOrder($id)));
    }
}
