<?php

namespace Ledc\CrmebIntraCity\locker;

use Ledc\ThinkModelTrait\RedisLocker;

/**
 * 同城配送订单操作锁
 * @package Ledc\CrmebIntraCity
 * @method static RedisLocker create(int $id)
 * @method static RedisLocker changeAddress(int $id)
 * @method static RedisLocker changeExpectedFinishedTime(int $id)
 */
class OrderLocker extends RedisLocker
{
}