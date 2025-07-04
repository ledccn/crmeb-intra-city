<?php

use think\migration\db\Column;
use think\migration\Migrator;

/**
 * 更新系统店铺表，闪送相关的字段
 */
class UpdateSystemStoreShanSong extends Migrator
{
    /**
     * Change Method.
     */
    public function change()
    {
        $table = $this->table('system_store');
        $table->addColumn(Column::string(\Ledc\ShanSong\Config::CONFIG_PREFIX . 'store_id')->setComment('闪送店铺ID')->setLimit(20)->setNull(false)->setDefault(''))
            ->addColumn(Column::unsignedInteger(\Ledc\ShanSong\Config::CONFIG_PREFIX . 'city_id')->setComment('闪送店铺所在城市ID')->setNull(false)->setDefault(0))
            ->addColumn(Column::string(\Ledc\ShanSong\Config::CONFIG_PREFIX . 'city_name')->setComment('闪送店铺所在城市名称')->setLimit(100)->setNull(false)->setDefault(''))
            ->addColumn(Column::string(\Ledc\ShanSong\Config::CONFIG_PREFIX . 'latitude')->setComment('闪送纬度（百度坐标系）')->setLimit(20)->setNull(false)->setDefault(''))
            ->addColumn(Column::string(\Ledc\ShanSong\Config::CONFIG_PREFIX . 'longitude')->setComment('闪送经度（百度坐标系）')->setLimit(20)->setNull(false)->setDefault(''))
            ->addColumn(Column::string(\Ledc\ShanSong\Config::CONFIG_PREFIX . 'store_id_test')->setComment('闪送店铺ID(测试环境)')->setLimit(20)->setNull(false)->setDefault(''))
            ->addColumn(Column::unsignedInteger(\Ledc\ShanSong\Config::CONFIG_PREFIX . 'city_id_test')->setComment('闪送店铺所在城市ID(测试环境)')->setNull(false)->setDefault(0))
            ->update();
    }
}
