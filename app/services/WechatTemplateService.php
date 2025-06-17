<?php

namespace Ledc\CrmebIntraCity\services;

use app\jobs\TemplateJob;
use app\model\order\StoreOrder;
use app\model\service\StoreService;
use app\model\system\SystemNotification;
use app\model\user\User;
use app\model\wechat\WechatUser;
use Ledc\CrmebIntraCity\dao\OrderDao;
use Ledc\CrmebIntraCity\enums\NotificationTemplateEnums;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 微信模板消息服务
 */
class WechatTemplateService
{
    /**
     * 客服列表
     * @var Collection|StoreService[]
     */
    protected Collection $adminList;

    /**
     * 构造函数
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function __construct()
    {
        $this->adminList = StoreService::where(['status' => 1, 'notify' => 1])->field(['nickname', 'phone', 'uid', 'customer'])->select();
    }

    /**
     * 待发货超时提醒客服
     * @return bool
     * @throws DbException
     */
    public function sendAdminOrderTimeoutException(): bool
    {
        $notification = $this->getSystemNotification(NotificationTemplateEnums::ADMIN_ORDER_TIMEOUT_EXCEPTION);
        if (!$notification) {
            return false;
        }

        $query = OrderDao::queryPending();
        // 订单金额{{amount3.DATA}}
        // 生成时间{{time12.DATA}}
        // 待发笔数{{character_string13.DATA}}
        // 超时笔数{{character_string14.DATA}}
        // 门店名称{{thing17.DATA}}
        $this->sendTemplate($notification, [
            'amount3' => bcadd((string)$query->sum('total_price'), '0', 2),
            'time12' => date('Y-m-d H:i:s'),
            'character_string13' => $query->count(),
            'character_string14' => OrderDao::queryPending(0)->count(),
            'thing17' => sys_config('site_name'),
        ]);
        return true;
    }

    /**
     * 订单异常
     * @param StoreOrder $storeOrder 订单
     * @param string $reason 异常原因（枚举值）
     * @return false|void
     */
    public function sendAdminOrderException(StoreOrder $storeOrder, string $reason = '')
    {
        $notification = $this->getSystemNotification(NotificationTemplateEnums::ADMIN_ORDER_EXCEPTION);
        if (!$notification) {
            return false;
        }
        // 订单号{{character_string1.DATA}}
        // 下单金额{{amount3.DATA}}
        // 下单时间{{time4.DATA}}
        // 异常时间{{time6.DATA}}
        // 异常原因{{const5.DATA}}
        $this->sendTemplate($notification, [
            'character_string1' => $storeOrder->order_id,
            'amount3' => bcadd((string)$storeOrder->total_price, '0', 2),
            'time4' => date('Y-m-d H:i:s', $storeOrder->add_time),
            'time6' => date('Y-m-d H:i:s'),
            //'const5' => $reason,
        ]);
    }

    /**
     * 配送订单待审核时通知客服
     * @param StoreOrder $storeOrder
     * @return void
     */
    public function sendAdminOrderAudit(StoreOrder $storeOrder)
    {
        $notification = $this->getSystemNotification(NotificationTemplateEnums::ADMIN_ORDER_AUDIT);
        if (!$notification) {
            return;
        }

        // 订单编号{{character_string1.DATA}}
        // 订单金额{{amount6.DATA}}
        // 审核时间{{time5.DATA}}
        $this->sendTemplate($notification, [
            'character_string1' => $storeOrder->order_id,
            'amount6' => bcadd((string)$storeOrder->total_price, '0', 2),
            'time5' => date('Y-m-d'),
        ]);
    }

    /**
     * 发送模板消息
     * @param SystemNotification $notification
     * @param array $data
     * @return void
     */
    public function sendTemplate(SystemNotification $notification, array $data)
    {
        $this->adminList->each(function (StoreService $storeService) use ($notification, $data) {
            $openid = $this->getOpenid($storeService->uid);
            if ($openid) {
                $templateId = $notification->wechat_tempid;
                $link = $notification->wechat_link ?: null;
                $wechat_to_routine = $notification->wechat_to_routine;
                //放入队列执行
                TemplateJob::dispatch('doJob', ['wechat', $openid, $templateId, $data, $link, null, $wechat_to_routine]);
            }
        });
    }

    /**
     * 根据UID获取openid
     * @param int $uid 用户 UID
     * @return string
     */
    protected function getOpenid(int $uid): string
    {
        $user = User::findOrEmpty($uid);
        if ($user->isEmpty() || $user->is_del) {
            return '';
        }
        return WechatUser::where('uid', $uid)->where('user_type', 'wechat')->value('openid', '');
    }

    /**
     * 获取通知模板
     * @param string $mark 标记
     * @return SystemNotification|null
     */
    protected function getSystemNotification(string $mark): ?SystemNotification
    {
        $notification = SystemNotification::where('mark', $mark)->findOrEmpty();
        if ($notification->isEmpty()) {
            return null;
        }

        if ($notification->is_wechat !== 1 || !$notification->wechat_tempid) {
            // 不存在 或 未启用
            return null;
        }
        return $notification;
    }
}
