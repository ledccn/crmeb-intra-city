<?php

namespace Ledc\CrmebIntraCity\api;

use app\model\order\StoreOrder;
use app\model\user\UserAddress;
use app\Request;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\locker\OrderLocker;
use Ledc\CrmebIntraCity\model\EbStoreOrderChangeAddress;
use Ledc\CrmebIntraCity\services\OrderAddressValidateService;
use Ledc\CrmebIntraCity\services\OrderChangeService;
use Ledc\CrmebIntraCity\services\StoreOrderChangeAddressService;
use Ledc\CrmebIntraCity\services\WechatTemplateService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 订单
 */
class OrderController
{
    /**
     * 获取订单
     * @param int $id 订单表主键
     * @return StoreOrder
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    protected function getStoreOrder(int $id): StoreOrder
    {
        /** @var StoreOrder $storeOrder */
        $storeOrder = StoreOrder::where('id', $id)->where('uid', \request()->uid())->findOrFail();
        return $storeOrder;
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
        $query = EbStoreOrderChangeAddress::queryByUser($oid, \request()->uid());

        return response_json()->success('ok', [
            'count' => $query->count(),
            'list' => $query->select()->toArray(),
        ]);
    }

    /**
     * 取消（自动退款）补差价订单
     * @param string $order_number
     * @return Response
     */
    public function cancelChangeOrderAddress(string $order_number): Response
    {
        $orderChangeAddress = EbStoreOrderChangeAddress::uniqueQuery($order_number);
        if ($orderChangeAddress->isEmpty()) {
            return response_json()->fail('补差价内部单号不存在');
        }

        if ($orderChangeAddress->uid !== \request()->uid()) {
            return response_json()->fail('订单不属于当前用户');
        }

        if ($orderChangeAddress->isLocked()) {
            return response_json()->fail('订单已锁定，不支持取消');
        }

        $locker = OrderLocker::changeAddress($orderChangeAddress->oid);
        if (!$locker->acquire()) {
            return response_json()->fail('未获取到锁，请稍后再试');
        }

        try {
            $msg = $orderChangeAddress->db()->transaction(function () use ($orderChangeAddress) {
                $storeOrder = $orderChangeAddress->getStoreOrder();
                if ($storeOrder && $storeOrder->change_user_address_id === $orderChangeAddress->id) {
                    $storeOrder->change_user_address_id = 0;
                    $storeOrder->save();
                }

                if ($orderChangeAddress->canRefund()) {
                    $service = new StoreOrderChangeAddressService($orderChangeAddress);
                    $service->refund('用户申请退款');
                    return '退款成功';
                } else {
                    $orderChangeAddress->delete();
                    return '申请取消成功';
                }
            });

            return response_json()->success($msg);
        } catch (Throwable $throwable) {
            return response_json()->fail($throwable->getMessage());
        }
    }

    /**
     * 生成补差价订单的支付参数
     * @param string $order_number 补差价内部单号
     * @param Request $request 请求对象
     * @return Response
     */
    public function payChangeOrderAddress(string $order_number, Request $request): Response
    {
        $quitUrl = $request->post('quitUrl/s', '');

        $options = ['quitUrl' => $quitUrl];
        $orderChangeAddress = EbStoreOrderChangeAddress::uniqueQuery($order_number);
        if ($orderChangeAddress->isEmpty()) {
            return response_json()->fail('补差价内部单号不存在');
        }

        $locker = OrderLocker::changeAddress($orderChangeAddress->oid);
        if (!$locker->acquire()) {
            return response_json()->fail('未获取到锁，请稍后再试');
        }

        //$orderChangeAddress->refreshOrderNumber();
        $service = new StoreOrderChangeAddressService($orderChangeAddress);
        [$statusType, $payInfo] = $service->pay($options);

        return response_json()->status($statusType, $payInfo);
    }

    /**
     * 提交变更订单地址的申请
     * @param int $id 订单表主键
     * @param Request $request 请求对象
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function changeOrderAddress(int $id, Request $request): Response
    {
        // 用户地址表主键
        $user_address_id = $request->post('user_address_id/d');
        if (!$user_address_id) {
            return response_json()->fail('请选择收货地址');
        }

        $storeOrder = $this->getStoreOrder($id);
        $locker = OrderLocker::changeAddress($id);
        if (!$locker->acquire()) {
            return response_json()->fail('未获取到锁，请稍后再试');
        }

        $userAddress = UserAddress::findOrFail($user_address_id);
        if ($storeOrder->uid !== $userAddress->uid) {
            return response_json()->fail('收货地址不属于当前用户');
        }

        $service = new OrderAddressValidateService($storeOrder);
        $result = $service->validateChangeAddress($userAddress);

        // 提醒客服
        WechatTemplateService::sendAdminOrderAudit($storeOrder, OrderChangeTypeEnums::CHANGE_ADDRESS);

        return response_json()->success('提交成功，请耐心等待审核或联系客服加快处理', $result->hidden(['oid', 'user_address_object', 'change_user_address_object'])->toArray());
    }

    /**
     * 提交变更订单期望送达时间的申请
     * @param int $id 订单表主键
     * @param Request $request 请求对象
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function changeExpectedFinishedTime(int $id, Request $request): Response
    {
        $expected_finished_start_time = $request->post('expected_finished_start_time/s');
        $expected_finished_end_time = $request->post('expected_finished_end_time/s');
        if (empty($expected_finished_start_time) || empty($expected_finished_end_time)) {
            return response_json()->fail('请选择期望送达时间');
        }

        $storeOrder = $this->getStoreOrder($id);
        $locker = OrderLocker::changeExpectedFinishedTime($storeOrder->id);
        if (!$locker->acquire()) {
            throw new ValidateException('未获取到锁，请稍后再试');
        }

        $service = new OrderChangeService($storeOrder);
        $service->validateExpectedFinishedTime($expected_finished_start_time, $expected_finished_end_time);

        // 提醒客服
        WechatTemplateService::sendAdminOrderAudit($storeOrder, OrderChangeTypeEnums::CHANGE_EXPECTED_FINISHED_TIME);

        return response_json()->success('提交成功，请耐心等待审核或联系客服加快处理');
    }
}
