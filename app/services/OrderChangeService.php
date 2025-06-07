<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\order\StoreOrder;
use Ledc\CrmebIntraCity\locker\OrderLocker;
use Ledc\CrmebIntraCity\parameters\HasStoreOrder;
use think\exception\ValidateException;

/**
 * 订单变更服务
 */
class OrderChangeService
{
    use HasStoreOrder;

    /**
     * 构造函数
     * @param StoreOrder $storeOrder
     */
    public function __construct(StoreOrder $storeOrder)
    {
        $this->setStoreOrder($storeOrder);
    }

    /**
     * 变更订单期望送达时间
     * @param string $expected_finished_start_time 预期送达开始时间
     * @param string $expected_finished_end_time 预期送达结束时间
     * @return bool
     * @throws ValidateException
     */
    public function changeExpectedFinishedTime(string $expected_finished_start_time, string $expected_finished_end_time): bool
    {
        if ($expected_finished_start_time > $expected_finished_end_time) {
            throw new ValidateException('期望送达时间错误');
        }

        $expected_finished_time = strtotime($expected_finished_start_time);
        if (date('Y-m-d', $expected_finished_time) !== date('Y-m-d', strtotime($expected_finished_end_time))) {
            throw new ValidateException('期望送达时间必须在同一天');
        }

        $storeOrder = $this->getStoreOrder();
        $locker = OrderLocker::changeExpectedFinishedTime($storeOrder->id);
        if (!$locker->acquire()) {
            throw new ValidateException('未获取到锁，请稍后再试');
        }

        if ($this->getStoreOrder()->wechat_processed) {
            throw new ValidateException('订单已呼叫配送员，如需修改请联系客服');
        }

        if ($expected_finished_time <= $storeOrder->expected_finished_time) {
            throw new ValidateException('修改后时间不能早于当前期望送达时间');
        }

        if (date('Y-m-d', $storeOrder->expected_finished_time) !== date('Y-m-d', $expected_finished_time)) {
            throw new ValidateException('修改后时间不能跨天');
        }

        // 数据库事务：修改订单
        $storeOrder->db()->transaction(function () use ($storeOrder, $expected_finished_time, $expected_finished_start_time, $expected_finished_end_time) {
            $storeOrder->expected_finished_time = $expected_finished_time;
            $storeOrder->expected_finished_start_time = $expected_finished_start_time;
            $storeOrder->expected_finished_end_time = $expected_finished_end_time;
            $storeOrder->save();
        });
        return true;
    }
}
