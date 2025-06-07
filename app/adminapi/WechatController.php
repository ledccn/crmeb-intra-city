<?php

namespace Ledc\CrmebIntraCity\adminapi;

use app\model\order\StoreOrder;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Response;
use Throwable;

/**
 * 微信同城配送
 */
class WechatController
{
    /**
     * 获取订单数据模型
     * @param int $id
     * @return StoreOrder
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    protected function getStoreOrder(int $id): StoreOrder
    {
        /** @var StoreOrder $order */
        $order = StoreOrder::findOrFail($id);
        return $order;
    }

    /**
     * 查询配送单
     * @param int $id
     * @return Response
     */
    public function queryOrder(int $id): Response
    {
        try {
            $order = $this->getStoreOrder($id);
            $expressApi = wechat_express_api();
            if (!$order->wechat_wx_order_id) {
                return response_json()->fail('此订单的微信订单编号为空，无法查询配送单明细');
            }

            $result = $expressApi->queryOrder($order->wechat_wx_order_id);
            return response_json()->success('success', $result);
        } catch (Throwable $e) {
            return app('json')->fail('查询配送单失败' . $e->getMessage());
        }
    }
}
