<?php

namespace Ledc\CrmebIntraCity\dao;

use app\model\order\StoreOrder;
use Ledc\CrmebIntraCity\enums\StoreOrderPaidEnums;
use Ledc\CrmebIntraCity\enums\StoreOrderRefundStatusEnums;
use Ledc\CrmebIntraCity\enums\StoreOrderStatusEnums;
use think\db\BaseQuery;
use think\db\Query;

/**
 * 订单数据访问层
 */
class OrderDao
{
    /**
     * 查询订单
     * - 默认查询180天前至今已支付且未退款且未取消的订单
     * @param int $days_ago 从X天前开始查询
     * @return StoreOrder|BaseQuery|Query
     */
    public static function query(int $days_ago = 180): Query
    {
        $model = new StoreOrder();
        return $model->db()->where('paid', StoreOrderPaidEnums::PAID)
            ->where('pay_time', '>=', time() - 86400 * $days_ago)
            ->where('status', '>=', StoreOrderStatusEnums::PENDING)
            ->where('is_del', 0)
            ->where('refund_status', StoreOrderRefundStatusEnums::NOT_REFUNDED);
    }

    /**
     * 查询即将超时的待发货订单
     * - 默认查询30天前至今已支付且未退款且未取消的待发货订单
     * - 包含待发单、派单中、待取货
     * @param int $warningWindow 预警时间窗口
     * @return StoreOrder|BaseQuery|Query
     */
    public static function queryPending(int $warningWindow = 3600): Query
    {
        $days_ago = 30;
        $model = new StoreOrder();
        return $model->db()->where('paid', StoreOrderPaidEnums::PAID)
            ->where('pay_time', '>=', time() - 86400 * $days_ago)
            ->where('is_del', 0)
            ->where('status', '=', StoreOrderStatusEnums::PENDING)
            ->where('refund_status', '=', StoreOrderRefundStatusEnums::NOT_REFUNDED)
            ->where('expected_finished_time', '<=', time() + $warningWindow);
    }

    /**
     * 查询已发货订单
     * - 默认查询30天前至今已支付且未退款且未取消的已发货订单
     * @param int $days_ago
     * @return StoreOrder|BaseQuery|Query
     */
    public static function queryShipped(int $days_ago = 30): Query
    {
        $model = new StoreOrder();
        return $model->db()->where('paid', StoreOrderPaidEnums::PAID)
            ->where('pay_time', '>=', time() - 86400 * $days_ago)
            ->where('is_del', 0)
            ->where('status', '=', StoreOrderStatusEnums::SHIPPED)
            ->where('refund_status', '=', StoreOrderRefundStatusEnums::NOT_REFUNDED);
    }
}
