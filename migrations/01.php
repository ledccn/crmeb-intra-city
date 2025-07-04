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
     */
    public function change()
    {
        $table = $this->table('store_product');
        $table->addColumn('cargo_type', AdapterInterface::PHINX_TYPE_STRING, ['comment' => '同城配送货物类型', 'limit' => 10, 'null' => false, 'default' => ''])
            ->update();
    }
}
