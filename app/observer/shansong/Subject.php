<?php

namespace Ledc\CrmebIntraCity\observer\shansong;

use Ledc\CrmebIntraCity\parameters\HasStoreOrder;
use Ledc\ShanSong\Parameters\OrderSubject;

/**
 * 处理订单状态回调数据报文
 */
class Subject extends OrderSubject
{
    use HasStoreOrder;

    /**
     * 初始化
     * @return void
     */
    protected function initialize(): void
    {
        $this->register = [
            OrderObserver::class,
        ];
    }
}
