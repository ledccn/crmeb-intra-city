<?php

use Ledc\CrmebIntraCity\LbsTencentHelper;
use think\migration\Migrator;

/**
 * 插入地理位置腾讯地图 KEY 配置
 */
class InsertSystemConfigLbs extends Migrator
{
    /**
     * Change Method.
     */
    public function change()
    {
        $systemConfigTab = $this->fetchRow("SELECT * FROM `eb_system_config_tab` WHERE `eng_title` = 'map_config'");
        if (empty($systemConfigTab)) {
            throw new InvalidArgumentException('未找到地图配置');
        }
        $config_tab_id = $systemConfigTab['id'];

        $systemConfigList = [
            [
                'menu_name' => LbsTencentHelper::LBS_TENCENT_KEY_DOMAIN,
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'value' => '',
                'info' => '腾讯地图KEY',
                'desc' => '使用场景WebServiceAPI：授权域名',
                'status' => 1,
            ],
            [
                'menu_name' => LbsTencentHelper::LBS_TENCENT_KEY_IP,
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => 'required:true',
                'width' => 100,
                'high' => 0,
                'value' => '',
                'info' => '腾讯地图KEY',
                'desc' => '使用场景WebServiceAPI：授权 IP 地址',
                'status' => 1,
            ],
            [
                'menu_name' => LbsTencentHelper::LBS_TENCENT_KEY_SIGNATURE,
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 100,
                'high' => 0,
                'value' => '',
                'info' => '腾讯地图KEY',
                'desc' => '【最佳实践】使用场景WebServiceAPI：签名校验',
                'status' => 1,
            ],
            [
                'menu_name' => LbsTencentHelper::LBS_TENCENT_KEY_SECRET,
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 100,
                'high' => 0,
                'value' => '',
                'info' => 'Secret key(SK)',
                'desc' => '【最佳实践】使用场景WebServiceAPI：签名校验 Secret key(SK)；此方法不必担心服务器换IP。',
                'status' => 1,
            ],
            [
                'menu_name' => LbsTencentHelper::LBS_TENCENT_KEY_APPID,
                'type' => 'text',
                'input_type' => 'input',
                'config_tab_id' => $config_tab_id,
                'required' => '',
                'width' => 100,
                'high' => 0,
                'value' => '',
                'info' => '腾讯地图KEY',
                'desc' => '使用场景微信小程序：授权APPID',
                'status' => 1,
            ],
        ];
        $this->table('system_config')
            ->insert($systemConfigList)
            ->saveData();
    }
}
