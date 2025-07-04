<?php

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\db\Column;
use think\migration\Migrator;

/**
 * 更新订单表，添加同城配送相关的字段
 */
class UpdateStoreOrderIntraCity extends Migrator
{
    /**
     * Change Method.
     */
    public function change()
    {
        $table = $this->table("store_order");
        $table->addColumn(Column::text('user_address_object')->setComment('收货用户地址对象')->setNull(true))
            ->addColumn(Column::unsignedInteger('change_user_address_id')->setComment('待变更的用户收货地址ID')->setNull(false)->setDefault(0)->setSigned(false))
            ->addColumn(Column::boolean('change_expected_finished_audit')->setComment('待变更期望送达审核状态')->setNull(false)->setDefault(0))
            ->addColumn(Column::string('user_lng')->setComment('收货用户地址经度')->setNull(false)->setDefault('0')->setLimit(20))
            ->addColumn(Column::string('user_lat')->setComment('收货用户地址维度')->setNull(false)->setDefault('0')->setLimit(20))
            ->addColumn(Column::mediumInteger('order_seq')->setComment('订单流水号')->setNull(false)->setDefault(0)->setSigned(false))
            ->addColumn(Column::boolean('wechat_processed')->setComment('是否呼叫骑手')->setNull(false)->setDefault(0))
            ->addColumn(Column::tinyInteger('trans_order_status')->setComment('同城配送状态')->setNull(false)->setDefault(0)->setSigned(false))
            ->addColumn('wechat_wx_store_id', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送门店编号', 'null' => false, 'limit' => 50, 'default' => ''])
            ->addColumn('wechat_wx_order_id', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送订单编号', 'null' => false, 'limit' => 50, 'default' => ''])
            ->addColumn('wechat_order_status', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送订单状态', 'null' => true, 'limit' => 20])
            ->addColumn('wechat_order_sub_status', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送订单子状态', 'null' => true, 'limit' => 20])
            ->addColumn('wechat_service_trans_id', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送运力', 'null' => false, 'limit' => 50, 'default' => ''])
            ->addColumn('wechat_trans_order_id', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送运力订单号', 'null' => false, 'limit' => 50, 'default' => ''])
            ->addColumn('wechat_waybill_id', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送运力配送单号', 'null' => false, 'limit' => 50, 'default' => ''])
            ->addColumn('wechat_fetch_code', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送取货码', 'null' => false, 'limit' => 20, 'default' => ''])
            ->addColumn('wechat_distance', AdapterInterface::PHINX_TYPE_INTEGER, ['comment' => '同城配送距离(米)', 'null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'signed' => false])
            ->addColumn('wechat_fee', AdapterInterface::PHINX_TYPE_INTEGER, ['comment' => '同城配送费用(分)', 'null' => false, 'limit' => MysqlAdapter::INT_REGULAR, 'default' => 0, 'signed' => false])
            ->addIndex('change_user_address_id')
            ->addIndex('change_expected_finished_audit')
            ->addIndex('wechat_wx_store_id')
            ->addIndex('wechat_wx_order_id')
            ->addIndex('wechat_processed')
            ->addIndex('trans_order_status')
            ->update();
    }
}
