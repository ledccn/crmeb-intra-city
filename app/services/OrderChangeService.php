<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\order\StoreOrder;
use app\model\order\StoreOrderStatus;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\parameters\HasStoreOrder;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 * 订单变更服务（变更期望送达时间）
 */
class OrderChangeService
{
    use HasStoreOrder;

    /**
     * 允许变更的字段
     */
    protected const allowChangeFields = ['expected_finished_time', 'expected_finished_start_time', 'expected_finished_end_time'];

    /**
     * 构造函数
     * @param StoreOrder $storeOrder
     */
    public function __construct(StoreOrder $storeOrder)
    {
        $this->setStoreOrder($storeOrder);
    }

    /**
     * 审核变更订单期望送达时间
     * @param bool $state 审核状态：true 通过，false 拒绝
     * @param string $reason 审核原因
     * @param bool $force 是否强制操作（审核状态true时，取消订单可能产生费用，需传true）
     * @return bool
     */
    public function auditChangeExpectedFinishedTime(bool $state, string $reason, bool $force): bool
    {
        $storeOrder = $this->getStoreOrder();
        // 记录订单变更日志
        StoreOrderStatus::create([
            'oid' => $storeOrder->id,
            'change_type' => OrderChangeTypeEnums::CHANGE_EXPECTED_FINISHED_TIME,
            'change_time' => time(),
            'change_message' => '审核变更期望送达时间：【' . ($state ? '通过' : '拒绝') . '】' . $reason,
        ]);
        $cacheData = $this->getCache();
        if (empty($cacheData)) {
            $storeOrder->change_expected_finished_audit = 0;
            $storeOrder->save();
            throw new ValidateException('申请变更期望送达时间的缓存为空');
        }

        // 数据库事务：修改订单
        $storeOrder->db()->transaction(function () use ($storeOrder, $cacheData, $state) {
            if ($state) {
                // 允许修改的字段
                foreach (self::allowChangeFields as $field) {
                    if (!empty($cacheData[$field])) {
                        $storeOrder->{$field} = $cacheData[$field];
                    }
                }
            }

            $storeOrder->change_expected_finished_audit = 0;
            $storeOrder->save();
        });
        $this->deleteCache();

        return true;
    }

    /**
     * 验证是否允许提交变更期望送达时间的申请
     * @param string $expected_finished_start_time 预期送达开始时间
     * @param string $expected_finished_end_time 预期送达结束时间
     * @return bool
     * @throws ValidateException
     */
    public function validateExpectedFinishedTime(string $expected_finished_start_time, string $expected_finished_end_time): bool
    {
        $expected_finished_time = strtotime($expected_finished_start_time);
        $end_time = strtotime($expected_finished_end_time);
        if ($expected_finished_time > $end_time) {
            throw new ValidateException('期望送达时间错误');
        }

        if (date('Y-m-d', $expected_finished_time) !== date('Y-m-d', $end_time)) {
            throw new ValidateException('期望送达时间必须在同一天');
        }

        $storeOrder = $this->getStoreOrder();
        if (!TransOrderStatusEnums::isAllowChangeAddressOrExpectedFinishedTime($storeOrder->trans_order_status)) {
            throw new ValidateException('订单已呼叫配送员，请联系客服');
        }

        // 调整：用户申请的期望送达时间允许跨天，但需客服审核
//        if ($expected_finished_time <= $storeOrder->expected_finished_time) {
//            throw new ValidateException('修改后时间不能早于当前期望送达时间');
//        }

//        if (date('Y-m-d', $storeOrder->expected_finished_time) !== date('Y-m-d', $expected_finished_time)) {
//            throw new ValidateException('修改后时间不能跨天');
//        }

        // 缓存用户提交的待变更数据
        $this->setCache(compact('expected_finished_time', 'expected_finished_start_time', 'expected_finished_end_time'));
        $storeOrder->change_expected_finished_audit = 1;
        $storeOrder->save();

        return true;
    }

    /**
     * 判断缓存是否存在
     * @return bool
     */
    public function hasCache(): bool
    {
        return Cache::has($this->getCacheKey());
    }

    /**
     * 获取缓存
     * @return array
     */
    public function getCache(): array
    {
        return Cache::get($this->getCacheKey(), []);
    }

    /**
     * 设置缓存
     * @param array $data
     * @return self
     */
    public function setCache(array $data): self
    {
        Cache::set($this->getCacheKey(), $data, $this->getCacheExpire());
        return $this;
    }

    /**
     * 删除缓存
     * @return self
     */
    public function deleteCache(): self
    {
        Cache::delete($this->getCacheKey());
        return $this;
    }

    /**
     * 获取缓存key
     * @return string
     */
    public function getCacheKey(): string
    {
        return 'changeExpectedFinishedTime:' . $this->getStoreOrder()->id;
    }

    /**
     * 获取缓存过期时间（10天）
     * @return int
     */
    public function getCacheExpire(): int
    {
        return 864000;
    }
}
