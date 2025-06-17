<?php

namespace Ledc\CrmebIntraCity\services;

use app\dao\order\StoreOrderDao;
use app\model\order\StoreOrder;
use app\services\order\StoreOrderRefundServices;
use crmeb\exceptions\AdminException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

/**
 * 创建订单
 */
class CreateOrderValidate
{
    /**
     * 订单同城配送发单前做前置检查
     * @param StoreOrder $storeOrder
     * @return StoreOrder
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function beforeValidate(StoreOrder $storeOrder): StoreOrder
    {
        $id = $storeOrder->id;
        /** @var StoreOrderDao $storeOrderDao */
        $storeOrderDao = app()->make(StoreOrderDao::class);
        /** @var StoreOrder $orderInfo */
        $orderInfo = $storeOrderDao->get($id, ['*'], ['pink']);
        /**
         * 前置检查
         */
        if (!$orderInfo) {
            throw new AdminException(400470);
        }
        if ($orderInfo->is_del) {
            throw new AdminException(400471);
        }
        if ($orderInfo->status) {
            throw new AdminException(400472);
        }
        if ($orderInfo->shipping_type == 2) {
            throw new AdminException(400473);
        }
        if (isset($orderInfo['pinkStatus']) && $orderInfo['pinkStatus'] != 2) {
            throw new AdminException(400474);
        }

        /** @var StoreOrderRefundServices $storeOrderRefundServices */
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        if ($storeOrderRefundServices->count(['store_order_id' => $id, 'refund_type' => [1, 2, 4, 5], 'is_cancel' => 0, 'is_del' => 0])) {
            throw new AdminException(400475);
        }

        if ($storeOrder->wechat_processed) {
            throw new ValidateException('该订单已呼叫骑手，请勿重复操作');
        }

        if ($storeOrder->change_user_address_id) {
            throw new ValidateException('存在修改收货地址，请处理后再发单');
        }
        if ($storeOrder->change_expected_finished_audit) {
            throw new ValidateException('存在修改配送时间，请处理后再发单');
        }

        return $orderInfo;
    }
}
