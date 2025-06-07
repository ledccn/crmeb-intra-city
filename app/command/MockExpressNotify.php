<?php
declare (strict_types=1);

namespace Ledc\CrmebIntraCity\command;

use Ledc\IntraCity\Enums\OrderStatusEnums;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use Throwable;

/**
 * 模拟微信小程序同城配送回调接口
 */
class MockExpressNotify extends Command
{
    /**
     * 模拟的状态
     */
    const MOCK_STATUS_MAP = [
        OrderStatusEnums::UINT_30000,
        OrderStatusEnums::UINT_40000,
        OrderStatusEnums::UINT_50000,
        OrderStatusEnums::UINT_70000,
    ];
    /**
     * @return void
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('mock:express')
            ->addArgument('wx_order_id', Argument::REQUIRED, "微信订单号")
            ->setDescription('模拟微信小程序同城配送回调接口');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output)
    {
        try {
            $wxOrderId = $input->getArgument('wx_order_id');
            $api = wechat_express_api();
            foreach (self::MOCK_STATUS_MAP as $k => $status) {
                $output->writeln('请求接口变更状态：' . $status);
                $output->writeln(json_encode($api->mockNotify($wxOrderId, (int)$status), JSON_UNESCAPED_UNICODE));
                if ($k !== count(self::MOCK_STATUS_MAP) - 1) {
                    $s = ($k + 1) * 10;
                    $output->writeln("休眠 $s 秒...");
                    sleep($s);
                }
            }

            $output->writeln(__CLASS__ . ' 执行完毕');
        } catch (Throwable $throwable) {
            // 指令输出
            $output->writeln((string)$throwable);
        }
    }
}
