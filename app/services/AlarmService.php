<?php

namespace Ledc\CrmebIntraCity\services;

use Ledc\CrmebIntraCity\dao\OrderDao;
use Ledc\CrmebIntraCity\events\AlarmPendingEvent;
use think\facade\Event;
use think\facade\Log;
use Throwable;

/**
 * 闹钟告警服务
 */
class AlarmService
{
    /**
     * 定时任务
     * - 请把方法注入到：\app\services\system\crontab\SystemCrontabServices::crontabCommandRun
     * @return void
     */
    public static function scheduler(): void
    {
        // 每2分钟执行一次
        //new Crontab('10 */2 * * * *', function () {
        //    AlarmService::scheduler();
        //});
        try {
            $h = date('H');
            if ($h < 8 || $h >= 20) {
                return;
            }

            // 查询即将超时的待发货订单（包含待发单、派单中、待取货）
            $count = OrderDao::queryPending()->count();
            if ($count > 0) {
                $query = OrderDao::queryPending()->fieldRaw('MD5(GROUP_CONCAT(id ORDER BY id ASC)) AS hash_value');
                $result = $query->select();
                if ($result->isEmpty()) {
                    return;
                }

                $result = $result->shift();
                $hash_value = $result->hash_value;
                $alarmPendingEvent = new AlarmPendingEvent($hash_value);
                if (!$alarmPendingEvent->hasCache()) {
                    $alarmPendingEvent->setCache();
                    // 调度事件
                    Event::trigger(AlarmPendingEvent::class, $alarmPendingEvent);
                    // 提醒客服
                    WechatTemplateService::sendAdminOrderTimeoutException();
                }
            }
        } catch (Throwable $throwable) {
            Log::error('订单预警服务异常：' . $throwable->getMessage());
        }
    }
}
