<?php

namespace Ledc\CrmebIntraCity\adminapi;

use Ledc\CrmebIntraCity\enums\StoreOrderRefundStatusEnums;
use Ledc\CrmebIntraCity\enums\StoreOrderStatusEnums;
use Ledc\CrmebIntraCity\ServiceTransEnums;
use Ledc\IntraCity\Enums\OrderStatusEnums as WechatOrderStatusEnums;
use Ledc\ShanSong\Enums\OrderStatusEnums as ShanSongOrderStatusEnums;
use think\Response;

/**
 * 枚举控制器
 */
class EnumsController
{
    /**
     * 同城配送运力
     * @return Response
     */
    public function ServiceTrans(): Response
    {
        return response_json()->success(ServiceTransEnums::listWithStatus());
    }

    /**
     * 订单状态枚举（CRMEB订单状态）
     * @return Response
     */
    public function StoreOrderStatus(): Response
    {
        return response_json()->success('success', StoreOrderStatusEnums::list());
    }

    /**
     * 订单退款状态枚举（CRMEB订单退款状态）
     * @return Response
     */
    public function StoreOrderRefundStatus(): Response
    {
        return response_json()->success('success', StoreOrderRefundStatusEnums::list());
    }

    /**
     * 同城配送运力单状态枚举（各运力独立）
     * @return Response
     */
    public function OrderStatus(): Response
    {
        $data = [
            ServiceTransEnums::TRANS_SHANSONG => [
                'wechat_order_status' => ShanSongOrderStatusEnums::cases(),
                'wechat_order_sub_status' => ShanSongOrderStatusEnums::subStatus(),
            ],
            ServiceTransEnums::TRANS_DADA => [
                'wechat_order_status' => WechatOrderStatusEnums::cases(),
                'wechat_order_sub_status' => [],
            ],
            ServiceTransEnums::TRANS_SFTC => [
                'wechat_order_status' => WechatOrderStatusEnums::cases(),
                'wechat_order_sub_status' => [],
            ],
        ];
        return response_json()->success('success', $data);
    }
}
