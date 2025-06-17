<?php

use Ledc\CrmebIntraCity\enums\NotificationTemplateEnums;
use think\migration\Migrator;

/**
 * 订单待发货提醒、异常订单处理通知
 */
class InsertSystemNotificationException extends Migrator
{
    /**
     * @return void
     */
    public function change()
    {
        $list = [
            [
                'mark' => NotificationTemplateEnums::ADMIN_ORDER_TIMEOUT_EXCEPTION,
                'name' => '待发货超时提醒客服',
                'title' => '即将超时的待发货订单提醒客服',
                'is_system' => 2,
                'system_title' => '存在即将超时的待发货订单',
                'system_text' => '存在即将超时的待发货订单，请及时处理',
                'is_wechat' => 1,
                'wechat_tempkey' => '46045',
                'wechat_content' => implode("\n", ['订单金额{{amount3.DATA}}', '生成时间{{time12.DATA}}', '待发笔数{{character_string13.DATA}}', '超时笔数{{character_string14.DATA}}', '门店名称{{thing17.DATA}}']),
                'wechat_tempid' => '',
                'wechat_to_routine' => 1,
                'type' => 2,
                'add_time' => time(),
            ],
            [
                'mark' => NotificationTemplateEnums::ADMIN_ORDER_EXCEPTION,
                'name' => '异常订单处理通知客服',
                'title' => '异常订单处理通知客服',
                'is_system' => 2,
                'system_title' => '存在异常订单',
                'system_text' => '存在异常订单，请及时处理',
                'is_wechat' => 1,
                'wechat_tempkey' => '54868',
                'wechat_content' => implode("\n", ['订单号{{character_string1.DATA}}', '下单金额{{amount3.DATA}}', '下单时间{{time4.DATA}}', '异常时间{{time6.DATA}}', '异常原因{{const5.DATA}}']),
                'wechat_tempid' => '',
                'wechat_to_routine' => 1,
                'type' => 2,
                'add_time' => time(),
            ],
            [
                'mark' => NotificationTemplateEnums::ADMIN_ORDER_AUDIT,
                'name' => '配送订单审核通知',
                'title' => '配送订单审核通知客服',
                'is_system' => 2,
                'system_title' => '配送订单审核通知',
                'system_text' => '存在待审核的配送订单，请及时处理',
                'is_wechat' => 1,
                'wechat_tempkey' => '55303',
                'wechat_content' => implode("\n", ['订单编号{{character_string1.DATA}}', '订单金额{{amount6.DATA}}', '审核时间{{time5.DATA}}']),
                'wechat_tempid' => '',
                'wechat_to_routine' => 1,
                'type' => 2,
                'add_time' => time(),
            ],
        ];
        $this->table('system_notification')
            ->insert($list)
            ->saveData();
    }
}
