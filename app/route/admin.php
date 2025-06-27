<?php

use app\adminapi\middleware\AdminAuthTokenMiddleware;
use app\adminapi\middleware\AdminCheckRoleMiddleware;
use app\adminapi\middleware\AdminLogMiddleware;
use app\http\middleware\AllowOriginMiddleware;
use Ledc\CrmebIntraCity\adminapi\DeliveryController;
use Ledc\CrmebIntraCity\adminapi\EnumsController;
use Ledc\CrmebIntraCity\adminapi\OrderController;
use Ledc\CrmebIntraCity\adminapi\PricingIncreaseStrategyController;
use Ledc\CrmebIntraCity\adminapi\ShanSongController;
use Ledc\CrmebIntraCity\adminapi\WechatController;
use think\facade\Route;
use think\Response;

/**
 * 同城配送 后台管理相关路由
 */
Route::group('intra-city-admin', function () {
    // ★同城配送运力ID枚举
    Route::get('trans', implode('@', [DeliveryController::class, 'trans']));
    // ★同城配送运力单状态枚举（所有运力共用）
    Route::get('status', implode('@', [DeliveryController::class, 'status']));
    // ★创建配送单（呼叫骑手）
    Route::post('create', implode('@', [DeliveryController::class, 'create']));
    // ★同城配送运力ID&状态枚举
    Route::get('trans_status', implode('@', [EnumsController::class, 'ServiceTrans']));
    // ★同城配送运力单状态枚举（各运力独立）
    Route::get('order_status', implode('@', [EnumsController::class, 'OrderStatus']));
    // 订单状态枚举（CRMEB订单状态）
    Route::get('store_order_status', implode('@', [EnumsController::class, 'StoreOrderStatus']));
    // 订单退款状态枚举（CRMEB订单退款状态）
    Route::get('store_order_refund_status', implode('@', [EnumsController::class, 'StoreOrderRefundStatus']));
    // 物品类型枚举（微信同城配送物品类型）
    Route::get('cargo_type', implode('@', [EnumsController::class, 'CargoType']));
    // 闪送物品类型标签枚举
    Route::get('good_type', implode('@', [EnumsController::class, 'ShanSongGoodType']));

    // 订单管理
    Route::group('store_order', function () {
        // 审核变更地址
        Route::put('audit_change_address/:id', implode('@', [OrderController::class, 'auditChangeAddress']));
        // 审核变更期望送达时间
        Route::put('audit_change_expected_finished_time/:id', implode('@', [OrderController::class, 'auditChangeExpectedFinishedTime']));
        // 统计
        Route::get('statistics', implode('@', [OrderController::class, 'statistics']));
        // 获取用户地址
        Route::get('user_address/:order_change_address', implode('@', [OrderController::class, 'userAddress']));
        // 获取变更期望送达时间缓存
        Route::get('change_expected_finished_time_cache/:id', implode('@', [OrderController::class, 'getChangeExpectedFinishedTimeCache']));
        // 查询即将超时的待发货订单
        Route::get('pending', implode('@', [OrderController::class, 'pending']));
        // 获取补差价变更地址订单列表
        Route::get('get_order_address_history_list/:oid', implode('@', [OrderController::class, 'getOrderAddressChangeHistoryList']));
    });

    // 同城配送加价策略
    Route::group('pricing-increase-strategy', function () {
        // 列表
        Route::get('index', implode('@', [PricingIncreaseStrategyController::class, 'index']));
        // 保存
        Route::post('save', implode('@', [PricingIncreaseStrategyController::class, 'save']));
        // 删除
        Route::delete('delete/:id', implode('@', [PricingIncreaseStrategyController::class, 'delete']));
    });

    // 闪送相关
    Route::group('shansong', function () {
        // 查询开通城市
        Route::get('open_cities_lists', implode('@', [ShanSongController::class, 'openCitiesLists']));
        // ★分页查询商户店铺
        Route::get('store_lists', implode('@', [ShanSongController::class, 'queryAllStores']));
        // ★查询城市可指定的交通工具
        Route::get('optional_travel_way/:city_id', implode('@', [ShanSongController::class, 'optionalTravelWay']));
        // ★订单计费
        Route::post('order_calculate', implode('@', [ShanSongController::class, 'orderCalculate']));
        // ★提交订单
        Route::post('order_place/:id', implode('@', [ShanSongController::class, 'orderPlace']));
        // ★订单加价
        Route::post('addition', implode('@', [ShanSongController::class, 'addition']));
        // ★查询订单详情
        Route::get('order_info/:id', implode('@', [ShanSongController::class, 'orderInfo']));
        // ★查询闪送员位置信息
        Route::get('courier_info/:id', implode('@', [ShanSongController::class, 'courierInfo']));
        // 查询订单续重加价金额
        Route::get('calculate_order_add_weight_fee', implode('@', [ShanSongController::class, 'calculateOrderAddWeightFee']));
        // 支付订单续重费用
        Route::post('pay_add_weight_fee', implode('@', [ShanSongController::class, 'payAddWeightFee']));
        // ★订单预取消
        Route::post('cancel_pre/:id', implode('@', [ShanSongController::class, 'preAbortOrder']));
        // ★订单取消
        Route::post('cancel', implode('@', [ShanSongController::class, 'abortOrder']));
        // ★确认物品送回
        Route::post('confirm_goods_return/:id', implode('@', [ShanSongController::class, 'confirmGoodsReturn']));
        // ★查询账号额度
        Route::get('get_user_account', implode('@', [ShanSongController::class, 'getUserAccount']));
        // ★修改收件人手机号
        Route::post('update_to_mobile', implode('@', [ShanSongController::class, 'updateToMobile']));
        // 查询是否支持尊享送
        Route::get('quality_delivery_switch', implode('@', [ShanSongController::class, 'qualityDeliverySwitch']));
        // 查询尊享送达成状态
        Route::get('quality_delivery_status/:id', implode('@', [ShanSongController::class, 'qualityDeliveryStatus']));
    });

    /**
     * 微信同城配送相关
     */
    Route::group('wechat', function () {
        // 查询配送单
        Route::get('query_order/:id', implode('@', [WechatController::class, 'queryOrder']));
    });
    Route::miss(function () {
        if (app()->request->isOptions()) {
            $header = \think\Facade\Config::get('cookie.header');
            unset($header['Access-Control-Allow-Credentials']);
            return Response::create('ok')->code(200)->header($header);
        } else {
            return Response::create()->code(404);
        }
    });
})->middleware([
    AllowOriginMiddleware::class,
    AdminAuthTokenMiddleware::class,
    AdminCheckRoleMiddleware::class,
    AdminLogMiddleware::class
])->option(['mark' => 'intra-city-admin', 'mark_name' => '同城配送管理']);