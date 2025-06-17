<?php

namespace Ledc\CrmebIntraCity\api;

use app\model\order\StoreOrder;
use app\model\user\UserAddress;
use app\Request;
use Ledc\CrmebIntraCity\locker\OrderLocker;
use Ledc\CrmebIntraCity\services\OrderChangeService;
use Ledc\CrmebIntraCity\services\WechatTemplateService;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Response;

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
     * 提交变更订单地址的申请
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function changeOrderAddress(int $id, Request $request): Response
    {
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

        $storeOrder->change_user_address_id = $user_address_id;
        $storeOrder->save();

        // 提醒客服
        $wechatTemplateService = new WechatTemplateService();
        $wechatTemplateService->sendAdminOrderAudit($storeOrder);

        return response_json()->success('提交成功，请耐心等待审核或联系客服加快处理');
    }

    /**
     * 提交变更订单期望送达时间的申请
     * @param int $id
     * @param Request $request
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
        $wechatTemplateService = new WechatTemplateService();
        $wechatTemplateService->sendAdminOrderAudit($storeOrder);

        return response_json()->success('提交成功，请耐心等待审核或联系客服加快处理');
    }
}
