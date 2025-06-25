<?php

use think\migration\db\Column;
use think\migration\Migrator;

/**
 * 创建变更地址订单表
 */
class CreateChangeAddressOrder extends Migrator
{
    /**
     * Change Method.
     */
    public function change()
    {
        $table = $this->table('store_order_change_address', ['engine' => 'InnoDB', 'comment' => '变更地址订单表', 'signed' => false]);
        $table->addColumn(Column::integer('oid')->setSigned(false)->setNull(false)->setComment('订单ID'))
            ->addColumn(Column::integer('uid')->setSigned(false)->setNull(false)->setComment('用户ID'))
            ->addColumn(Column::string('order_number', 32)->setComment('内部单号')->setNull(false))
            ->addColumn(Column::string('pay_type', 32)->setComment('支付类型')->setNull(false)->setDefault(''))
            ->addColumn(Column::decimal('pay_price')->setComment('支付金额')->setSigned(false)->setNull(false)->setDefault(0))
            ->addColumn(Column::integer('pay_time')->setComment('支付时间')->setNull(false)->setDefault(0))
            ->addColumn(Column::boolean('paid')->setComment('支付状态')->setNull(false)->setDefault(0))
            ->addColumn(Column::string('pay_trade_no', 80)->setComment('支付单号')->setNull(false)->setDefault(''))
            ->addColumn(Column::text('user_address_object')->setComment('用户地址对象')->setNull(false))
            ->addColumn(Column::text('change_user_address_object')->setComment('变更后的用户地址对象')->setNull(false))
            ->addColumn(Column::string('change_reason')->setComment('变更原因')->setNull(false)->setDefault(''))
            ->addColumn(Column::decimal('change_diff_price')->setComment('补差价（正值用户需支付、负值客服退用户）')->setNull(false)->setDefault(0))
            ->addColumn(Column::boolean('refund_status')->setComment('退款状态')->setNull(false)->setDefault(0))
            ->addColumn(Column::decimal('refund_price')->setComment('退款金额')->setSigned(false)->setNull(false)->setDefault(0))
            ->addColumn(Column::string('refund_reason')->setComment('退款原因')->setNull(false)->setDefault(''))
            ->addColumn(Column::boolean('refund_locked')->setComment('退款已锁定，锁定后用户无法申请退款')->setNull(false)->setDefault(0))
            ->addTimestamps()
            ->addForeignKey('oid', 'store_order', 'id', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addForeignKey('uid', 'user', 'uid', ['delete' => 'CASCADE', 'update' => 'CASCADE'])
            ->addIndex(['oid'])
            ->addIndex(['uid'])
            ->addIndex(['order_number'], ['unique' => true])
            ->addIndex(['pay_type'])
            ->addIndex(['paid'])
            ->addIndex(['pay_time'])
            ->addIndex(['refund_status'])
            ->create();
    }
}
