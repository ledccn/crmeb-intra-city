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
     */
    public function change()
    {
        $table = $this->table('user_address');
        $table->addColumn(Column::string('map_name')->setComment('地标名称')->setNull(false)->setDefault(''))
            ->addColumn(Column::string('map_address')->setComment('地标完整地址')->setNull(false)->setDefault(''))
            ->update();
    }
}
