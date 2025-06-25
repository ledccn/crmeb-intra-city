<?php

namespace Ledc\CrmebIntraCity\model;

use app\model\order\StoreOrder;
use app\model\user\UserAddress;
use app\services\pay\PayServices;
use think\db\BaseQuery;
use think\db\Query;
use think\Model;

/**
 * 变更地址订单表
 */
class EbStoreOrderChangeAddress extends Model
{
    use HasEbStoreOrderChangeAddress;

    /**
     * 支付成功后的处理行为
     */
    public const PAY_SUCCESS_ACTION = 'freight';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'eb_store_order_change_address';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $pk = 'id';

    /**
     * 唯一查询
     * @param string $order_number 内部单号
     * @return self|null
     */
    public static function uniqueQuery(string $order_number): ?self
    {
        $model = static::where('order_number', $order_number)->findOrEmpty();
        return $model->isEmpty() ? null : $model;
    }

    /**
     * 通过订单表主键查询
     * @param int $oid 订单表主键
     * @return EbStoreOrderChangeAddress|Query|BaseQuery
     */
    public static function queryByOid(int $oid): Query
    {
        return static::where('oid', $oid);
    }

    /**
     * 通过订单表主键和用户ID查询
     * @param int $oid 订单表主键
     * @param int $uid 用户ID
     * @return EbStoreOrderChangeAddress|Query|BaseQuery
     */
    public static function queryByUser(int $oid, int $uid): Query
    {
        return static::where('oid', $oid)->where('uid', $uid);
    }

    /**
     * 创建变更地址订单
     * - 设置：订单主键、用户ID、支付时间、用户地址对象
     * @param StoreOrder $storeOrder
     * @param UserAddress $userAddress
     * @return self
     */
    public static function makeChangeAddress(StoreOrder $storeOrder, UserAddress $userAddress): self
    {
        $model = new EbStoreOrderChangeAddress();
        $model->oid = $storeOrder->id;
        $model->uid = $storeOrder->uid;
        $model->order_number = generate_order_number();
        $model->user_address_object = $storeOrder->user_address_object;
        $model->change_user_address_object = json_encode($userAddress, JSON_UNESCAPED_UNICODE);
        return $model;
    }

    /**
     * 刷新内部单号
     * @return self
     */
    public function refreshOrderNumber(): self
    {
        $this->order_number = generate_order_number();
        $this->save();
        return $this;
    }

    /**
     * 获取商城订单数据模型
     * @return StoreOrder|null
     */
    public function getStoreOrder(): ?StoreOrder
    {
        $storeOrder = StoreOrder::findOrEmpty($this->oid);
        return $storeOrder->isEmpty() ? null : $storeOrder;
    }

    /**
     * 判断是否允许退款
     */
    public function canRefund(): bool
    {
        return $this->isPaid()
            && $this->pay_type === PayServices::WEIXIN_PAY
            && 0 < $this->pay_price
            && !$this->refund_status;
    }

    /**
     * 是否已支付
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->getAttr('paid') && $this->getAttr('pay_time') > 0;
    }

    /**
     * 是否已退款
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->refund_status;
    }

    /**
     * 是否已锁定
     * - 锁定后用户无法申请退款
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->refund_locked;
    }

    /**
     * 锁定
     * @return $this
     */
    public function setLocked(): self
    {
        $this->refund_locked = 1;
        $this->save();
        return $this;
    }

    /**
     * 设置支付状态
     * @param bool $paid
     * @return $this
     */
    public function setPaidStatus(bool $paid): self
    {
        if ($paid) {
            $this->paid = 1;
            $this->pay_time = time();
        } else {
            $this->paid = 0;
        }
        return $this;
    }
}
