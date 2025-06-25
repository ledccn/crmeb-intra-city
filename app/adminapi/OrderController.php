<?php

namespace Ledc\CrmebIntraCity\adminapi;

use app\model\order\StoreOrder;
use app\Request;
use Ledc\CrmebIntraCity\dao\OrderDao;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\locker\OrderLocker;
use Ledc\CrmebIntraCity\model\EbStoreOrderChangeAddress;
use Ledc\CrmebIntraCity\services\OrderAddressService;
use Ledc\CrmebIntraCity\services\OrderChangeService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;

/**
 * 订单管理
 */
class OrderController
{
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
     * 审核变更地址
     * @param int $id 订单表主键
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function auditChangeAddress(int $id, Request $request): Response
    {
        [$state, $reason, $force, $reprint] = $request->postMore([
            ['state/b', false],
            ['reason/s', ''],
            ['force/b', false],
            ['reprint/b', true],
        ], true);

        $storeOrder = $this->getStoreOrder($id);
        $locker = OrderLocker::changeAddress($id);
        if (!$locker->acquire()) {
            return response_json()->fail('未获取到锁，请稍后再试');
        }
        if (!$storeOrder->change_user_address_id) {
            throw new ValidateException('订单未申请变更收货人信息');
        }

        $service = new OrderAddressService($storeOrder);
        $service->auditChangeAddress($state, $reason, $force, $reprint);

        return response_json()->success();
    }

    /**
     * 审核变更期望送达时间
     * @param int $id 订单表主键
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function auditChangeExpectedFinishedTime(int $id, Request $request): Response
    {
        [$state, $reason, $force] = $request->postMore([
            ['state/b', false],
            ['reason/s', ''],
            ['force/b', false],
        ], true);

        $storeOrder = $this->getStoreOrder($id);
        $locker = OrderLocker::changeExpectedFinishedTime($storeOrder->id);
        if (!$locker->acquire()) {
            throw new ValidateException('未获取到锁，请稍后再试');
        }
        if (!$storeOrder->change_expected_finished_audit) {
            throw new ValidateException('订单未申请变更期望送达时间');
        }

        $service = new OrderChangeService($storeOrder);
        $service->auditChangeExpectedFinishedTime($state, $reason, $force);
        return response_json()->success();
    }

    /**
     * 订单统计
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function statistics(Request $request): Response
    {
        $query = OrderDao::query()
            ->field(['trans_order_status', 'count(*) AS count'])
            ->group('trans_order_status');
        $counts = $query->select()->column('count', 'trans_order_status');

        return response_json()->success('ok', TransOrderStatusEnums::listWithCounts($counts));
    }

    /**
     * 查询即将超时的待发货订单
     * - 包含待发单、派单中、待取货
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function pending(Request $request): Response
    {
        $warningWindow = $request->get('warning_window/d', 3600);
        $query = OrderDao::queryPending($warningWindow);
        $result = $query->field('id,order_id,uid,paid,pay_time,pay_type,pay_price,is_del,status,refund_status,trans_order_status,expected_finished_time,expected_finished_start_time,expected_finished_end_time')
            ->select();
        return response_json()->success('ok', $result->toArray());
    }

    /**
     * 获取用户地址
     * @param int $order_change_address 变更地址订单表的主键
     * @return Response
     */
    public function userAddress(int $order_change_address): Response
    {
        /** @var EbStoreOrderChangeAddress $orderChangeAddress */
        $orderChangeAddress = EbStoreOrderChangeAddress::findOrEmpty($order_change_address);
        if ($orderChangeAddress->isEmpty()) {
            return response_json()->fail('用户地址不存在');
        }
        return response_json()->success('ok', json_decode($orderChangeAddress->change_user_address_object, true));
    }

    /**
     * 获取变更期望送达时间缓存
     * @param int $id 订单表主键
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function getChangeExpectedFinishedTimeCache(int $id, Request $request): Response
    {
        $storeOrder = $this->getStoreOrder($id);
        $service = new OrderChangeService($storeOrder);
        return response_json()->success('ok', $service->getCache());
    }

    /**
     * 获取补差价变更地址订单列表
     * @param int $oid 订单表主键
     * @param Request $request 请求对象
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getOrderAddressChangeHistoryList(int $oid, Request $request): Response
    {
        $query = EbStoreOrderChangeAddress::queryByOid($oid);

        return response_json()->success('ok', [
            'count' => $query->count(),
            'list' => $query->select()->toArray(),
        ]);
    }
}
