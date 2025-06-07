<?php

use think\migration\Migrator;

/**
 * 闪送配置
 */
class InsertSystemConfigShanSong extends Migrator
{
    protected const eng_title = 'shan_song';

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
        $systemConfigTab = $this->fetchRow("SELECT * FROM `eb_system_config_tab` WHERE `eng_title` = 'system_config'");
        if (empty($systemConfigTab)) {
            throw new InvalidArgumentException('未找到系统配置');
        }
        // 插入配置分类表
        $this->table('system_config_tab')->insert([
            'pid' => $systemConfigTab['id'],
            'title' => '闪送',
            'eng_title' => 'shan_song',
            'status' => 1,
            'info' => 0,
            'icon' => 's-promotion',
            'type' => 0,
            'sort' => 0,
            'menus_id' => $systemConfigTab['menus_id'],
        ])->saveData();

        $configTab = $this->fetchRow("SELECT * FROM `eb_system_config_tab` WHERE `eng_title` = 'shan_song'");
        $config_tab_id = $configTab['id'];

        $systemConfigList = [
            [
                'menu_name' => \Ledc\ShanSong\Config::CONFIG_PREFIX . 'shopId',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '商户Shop ID',
                'desc' => '应用信息，商户ID',
                'status' => 1,
            ],
            [
                'menu_name' => \Ledc\ShanSong\Config::CONFIG_PREFIX . 'clientId',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '商户Client ID',
                'desc' => '应用信息，App-key',
                'status' => 1,
            ],
            [
                'menu_name' => \Ledc\ShanSong\Config::CONFIG_PREFIX . 'appSecret',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '商户App_secret',
                'desc' => '应用信息，App-密钥',
                'status' => 1,
            ],
            [
                'menu_name' => \Ledc\ShanSong\Config::CONFIG_PREFIX . 'testShopId',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '测试环境商户Shop ID',
                'desc' => '应用信息，测试环境商户ID',
                'status' => 1,
            ],
            [
                'menu_name' => \Ledc\ShanSong\Config::CONFIG_PREFIX . 'debug',
                'type' => 'switch',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 0,
                'high' => 0,
                'info' => '启用测试环境',
                'desc' => '【切换环境后，需要提货点重新绑定闪送店铺】开启时使用测试环境，关闭时使用正式环境',
                'status' => 1,
            ],
            [
                'menu_name' => \Ledc\ShanSong\Config::CONFIG_PREFIX . 'enabled',
                'type' => 'switch',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 0,
                'high' => 0,
                'info' => '启用闪送',
                'desc' => '闪送的全局开关',
                'status' => 1,
            ],
        ];
        $this->table('system_config')
            ->insert($systemConfigList)
            ->saveData();
    }
}
