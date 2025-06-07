<?php

namespace Ledc\CrmebIntraCity;

use Ledc\ThinkModelTrait\Contracts\HasMigrationCommand;
use think\console\Input;
use think\console\Output;

/**
 * 安装数据库迁移文件
 */
class Command extends \think\console\Command
{
    use HasMigrationCommand;

    /**
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('install:migrate:crmeb-intra-city')
            ->setDescription('安装插件的数据库迁移文件');

        // 迁移文件映射
        $this->setFileMaps([
            // 商品表
            'UpdateStoreProductIntraCity' => dirname(__DIR__) . '/migrations/01.php',
            // 用户地址表
            'UpdateUserAddressMaps' => dirname(__DIR__) . '/migrations/02.php',
            // 订单表
            'UpdateStoreOrderIntraCity' => dirname(__DIR__) . '/migrations/03.php',
            // 配置表：微信同城配送
            'InsertSystemConfigIntraCity' => dirname(__DIR__) . '/migrations/04.php',
            // 配置表：闪送
            'InsertSystemConfigShanSong' => dirname(__DIR__) . '/migrations/05.php',
            // 系统店铺表
            'UpdateSystemStoreShanSong' => dirname(__DIR__) . '/migrations/06.php',
            // 同城配送加价策略表
            'CreatePricingIncreaseStrategy' => dirname(__DIR__) . '/migrations/07.php',
            // 订单待发货提醒、异常订单处理通知
            'InsertSystemNotificationException' => dirname(__DIR__) . '/migrations/08.php',
        ]);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        $this->eachFileMaps($input, $output);
    }
}
