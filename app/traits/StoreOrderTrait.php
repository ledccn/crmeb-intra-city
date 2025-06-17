<?php

namespace Ledc\CrmebIntraCity\traits;

use app\model\user\UserAddress;

/**
 * 订单表
 * @property string|UserAddress $user_address_object 用户地址表对象
 * @property integer $change_user_address_id 待变更的用户收货地址ID
 * @property int|bool $change_expected_finished_audit 待变更期望送达审核状态
 * @property integer $expected_finished_time 预期送达时间
 * @property string $expected_finished_start_time 预期送达开始时间
 * @property string $expected_finished_end_time 预期送达结束时间
 * @property string $user_lng 收货用户地址经度
 * @property string $user_lat 收货用户地址维度
 * @property integer $order_seq 当期订单序号
 * @property integer $wechat_processed 已呼叫骑手
 * @property integer $trans_order_status 同城配送状态
 * @property string $wechat_order_status 同城配送订单状态
 * @property string $wechat_order_sub_status 子状态
 * @property string $wechat_wx_store_id 微信门店编号
 * @property string $wechat_wx_order_id 微信订单编号
 * @property string $wechat_service_trans_id 配送运力
 * @property integer $wechat_distance 配送距离(米)
 * @property string $wechat_trans_order_id 运力订单号
 * @property string $wechat_waybill_id 运力配送单号
 * @property integer $wechat_fee 配送费(分)
 * @property string $wechat_fetch_code 取货码
 * @property integer $trans_order_create_time 同城配送运力单创建时间
 * @property integer $trans_order_update_time 同城配送运力单更新时间
 */
trait StoreOrderTrait
{
}
