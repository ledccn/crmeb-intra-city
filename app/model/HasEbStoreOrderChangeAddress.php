<?php

namespace Ledc\CrmebIntraCity\model;

/**
 * eb_store_order_change_address 变更地址订单表
 * @property integer $id (主键)
 * @property integer $oid 订单ID
 * @property integer $uid 用户ID
 * @property string $order_number 内部单号
 * @property string $pay_type 支付类型
 * @property string $pay_price 支付金额
 * @property integer $pay_time 支付时间
 * @property integer $paid 支付状态
 * @property string $pay_trade_no 支付单号
 * @property string $user_address_object 用户地址对象
 * @property string $change_user_address_object 变更后的用户地址对象
 * @property string $change_reason 变更原因
 * @property string $change_diff_price 补差价（正值用户需支付、负值客服退用户）
 * @property integer $refund_status 退款状态
 * @property string $refund_price 退款金额
 * @property string $refund_reason 退款原因
 * @property integer $refund_locked 退款已锁定，锁定后用户无法申请退款
 * @property mixed $create_time
 * @property mixed $update_time
 */
trait HasEbStoreOrderChangeAddress
{
}
