<?php

use Phinx\Db\Adapter\AdapterInterface;
use think\migration\Migrator;

/**
 * 更新商品表：添加同城配送货物类型字段
 */
class UpdateStoreProductIntraCity extends Migrator
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
        $table = $this->table('store_product');
        $table->addColumn('cargo_type', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送货物类型', 'limit' => 10, 'null' => false, 'default' => ''])
            ->update();
    }
}
