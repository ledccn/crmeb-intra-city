<?php

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use think\migration\db\Column;
use think\migration\Migrator;

/**
 * 同城配送加价策略表
 */
class CreatePricingIncreaseStrategy extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('pricing_increase_strategy', [
            'comment' => '同城配送加价策略表',
            'signed' => false,
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci'
        ]);

        $table->addColumn('strategy_name', 'string', ['limit' => 255, 'comment' => '策略名称', 'null' => false])
            ->addColumn('minutes_until_increase', 'integer', ['comment' => 'X分钟未接单开始加价', 'limit' => MysqlAdapter::INT_TINY, 'signed' => false, 'null' => false, 'default' => 10])
            ->addColumn(Column::tinyInteger('increase_interval_minutes')->setSigned(false)->setComment('每隔X分钟加价一次')->setNull(false)->setDefault(3))
            ->addColumn(Column::integer('increase_amount')->setSigned(false)->setComment('每次加价金额：分')->setNull(false)->setDefault(200))
            ->addColumn(Column::unsignedInteger('max_increase_amount')->setComment('加价上限金额：分')->setNull(false)->setDefault(0))
            ->addColumn('is_active', 'boolean', ['default' => true, 'comment' => '是否启用', 'null' => false])
            ->addColumn('create_time', AdapterInterface::PHINX_TYPE_DATETIME, ['comment' => '创建时间', 'null' => false, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('update_time', AdapterInterface::PHINX_TYPE_DATETIME, ['comment' => '更新时间', 'null' => true, 'default' => 'CURRENT_TIMESTAMP', 'update' => 'CURRENT_TIMESTAMP'])
            ->addIndex(['is_active'], ['name' => 'idx_is_active'])
            ->create();
    }
}
