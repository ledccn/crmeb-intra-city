<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\system\store\SystemStore;
use think\exception\ValidateException;

/**
 * 店铺服务
 */
class SystemStoreService
{
    /**
     * 从数据库查询店铺提货点
     * @param int $id CRMEB系统提货点ID
     * @return SystemStore
     */
    public static function getSystemStore(int $id): SystemStore
    {
        if ($id) {
            /** @var SystemStore $systemStore */
            $systemStore = SystemStore::findOrEmpty($id);
        } else {
            /** @var SystemStore $systemStore */
            $systemStore = SystemStore::where(['is_show' => 1, 'is_del' => 0])
                ->order('id', 'asc')
                ->findOrEmpty();
        }
        if ($systemStore->isEmpty()) {
            throw new ValidateException('店铺提货点设置为空');
        }

        return $systemStore;
    }
}
