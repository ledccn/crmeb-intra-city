<?php

use think\migration\Migrator;

/**
 * 微信同城配送系统配置
 */
class InsertSystemConfigIntraCity extends Migrator
{
    /**
     * Change Method.
     */
    public function change()
    {
        $routineConfigTab = $this->fetchRow("SELECT * FROM `eb_system_config_tab` WHERE `eng_title` = 'routine'");
        if (empty($routineConfigTab)) {
            throw new InvalidArgumentException('未找到小程序配置');
        }
        // 插入配置分类表
        $this->table('system_config_tab')->insert([
            'pid' => $routineConfigTab['id'],
            'title' => '同城配送',
            'eng_title' => 'wechat_express',
            'status' => 1,
            'info' => 0,
            'icon' => 's-promotion',
            'type' => 0,
            'sort' => 0,
            'menus_id' => $routineConfigTab['menus_id'],
        ])->saveData();

        $configTab = $this->fetchRow("SELECT * FROM `eb_system_config_tab` WHERE `eng_title` = 'wechat_express'");
        $config_tab_id = $configTab['id'];

        $systemConfigList = [
            [
                'menu_name' => 'wechat_aes_sn',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '对称密钥的编号',
                'desc' => '对称密钥的编号',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_aes_key',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '对称密钥',
                'desc' => '对称密钥',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_rsa_sn',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '非对称密钥的编号',
                'desc' => '非对称密钥的编号',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_rsa_public_key',
                'type' => 'textarea',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 5,
                'info' => '非对称密钥的公钥',
                'desc' => '非对称密钥的公钥',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_rsa_private_key',
                'type' => 'textarea',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 5,
                'info' => '非对称密钥的私钥',
                'desc' => '非对称密钥的私钥',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_cert_sn',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '开放平台证书的编号',
                'desc' => '开放平台证书的编号',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_cert_key',
                'type' => 'textarea',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 5,
                'info' => '开放平台证书',
                'desc' => '开放平台证书',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_callback_url',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true,url:true',
                'width' => 100,
                'high' => 0,
                'info' => '配送单回调URL',
                'desc' => '当订单状态发生变更时，会由微信服回调接入方的回调地址进行状态变更的通知，回调接口地址需要接入方在下单时传入。',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_wx_store_id',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '微信门店编号',
                'desc' => '微信门店编号',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_order_detail_path',
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'info' => '跳转商家订单页面路径',
                'desc' => '物流轨迹页面跳转到商家小程序的订单页面路径参数，期望向用户展示商品订单详情。',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_enable',
                'type' => 'switch',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 0,
                'high' => 0,
                'info' => '启用同城配送',
                'desc' => '启用同城配送',
                'status' => 1,
            ],
            [
                'menu_name' => 'wechat_use_sandbox',
                'type' => 'switch',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 0,
                'high' => 0,
                'info' => '启用沙箱环境',
                'desc' => '启用沙箱环境',
                'status' => 1,
            ],
        ];
        $this->table('system_config')
            ->insert($systemConfigList)
            ->saveData();
    }
}
