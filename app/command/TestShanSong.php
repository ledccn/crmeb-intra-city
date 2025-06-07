<?php
declare (strict_types=1);

namespace Ledc\CrmebIntraCity\command;

use Ledc\CrmebIntraCity\parameters\ShanSongParameters;
use Ledc\CrmebIntraCity\ShanSongHelper;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use Throwable;

/**
 * 测试闪送接口命令
 */
class TestShanSong extends Command
{
    /**
     * 指令配置
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('test:shansong')
            ->addArgument('name', Argument::OPTIONAL, '指令名称')
            ->setDescription('测试闪送接口命令');
    }

    /**
     * 执行指令
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $merchant = ShanSongHelper::merchant();
            //$result = $merchant->openCitiesLists();
            //file_put_contents(runtime_path() . 'openCitiesLists.json', json_encode($result, JSON_UNESCAPED_UNICODE));
            $result = $merchant->queryAllStores();
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            var_dump($result);

            $propertiesWithComments = getClassPropertiesWithComments(ShanSongParameters::class);
            print_r($propertiesWithComments);
            // 指令输出
            $output->writeln('shansong');
        } catch (Throwable $e) {
            $output->writeln($e->getMessage());
        }
    }
}
