<?php

use think\migration\db\Column;
use think\migration\Migrator;

/**
 * 更新用户地址表，添加地图地标名称和地址
 */
class UpdateUserAddressMaps extends Migrator
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
        $table = $this->table('user_address');
        $table->addColumn(Column::string('map_name')->setComment('地标名称')->setNull(false)->setDefault(''))
            ->addColumn(Column::string('map_address')->setComment('地标完整地址')->setNull(false)->setDefault(''))
            ->update();
    }
}
