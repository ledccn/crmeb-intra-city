<?php
declare (strict_types=1);

namespace Ledc\CrmebIntraCity\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use Throwable;

/**
 * 测试微信小程序同城配送
 */
class TestExpress extends Command
{
    /**
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('test:express')
            ->setDescription('测试微信小程序同城配送');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $api = wechat_express_api();
            var_dump($api->queryStore());
            // 指令输出
            $output->writeln('test:express');
        } catch (Throwable $throwable) {
            // 指令输出
            $output->writeln((string)$throwable);
        }
    }
}
