<?php

use app\api\middleware\AuthTokenMiddleware;
use app\api\middleware\StationOpenMiddleware;
use app\http\middleware\AllowOriginMiddleware;
use app\Request;
use Ledc\CrmebIntraCity\api\OrderController;
use Ledc\CrmebIntraCity\api\ShanSongController;
use Ledc\CrmebIntraCity\api\TencentMapController;
use Ledc\CrmebIntraCity\api\WechatController;
use Ledc\ThinkModelTrait\Contracts\LockerParameters;
use Ledc\ThinkModelTrait\Middleware\LimiterMiddleware;
use Ledc\ThinkModelTrait\Middleware\LockerMiddleware;
use think\facade\Route;

/**
 * 测试接口
 */
Route::group('test_intra_city', function () {
    // 测试锁中间件
    Route::get('locker', function (Request $request) {
        return response_json()->success('success', [
            $request->method(true),
            $request->rule()->getRule(),
            $request->rule()->getMethod(),
        ]);
    })->middleware(LockerMiddleware::class, LockerParameters::builderIP(5, false));

    // 测试限流中间件
    Route::get('limiter', function (Request $request) {
        return response_json()->success('success', [
            $request->method(true),
            $request->rule()->getRule(),
            $request->rule()->getMethod(),
        ]);
    })->middleware(LimiterMiddleware::class, ['limit' => 5, 'window' => 5]);
});

/**
 * 同城配送回调接口
 */
Route::group('intra_city_callback', function () {
    // 闪送：订单状态回调
    Route::any('shansong', implode('@', [ShanSongController::class, 'notifyCallback']));
    // 微信小程序同城配送：订单状态回调
    Route::any('wechat', implode('@', [WechatController::class, 'notifyCallback']));
});

/**
 * 同城配送 用户相关路由
 */
Route::group('intra_city_api', function () {
    // 订单相关
    Route::group('store_order', function () {
        // 提交变更订单地址的申请
        Route::post('change_order_address/:id', implode('@', [OrderController::class, 'changeOrderAddress']));
        // 变更订单期望送达时间
        Route::post('change_expected_finished_time/:id', implode('@', [OrderController::class, 'changeExpectedFinishedTime']));
    });

    // 闪送相关
    Route::group('shansong', function () {
        // 查询订单详情
        Route::get('order_info/:id', implode('@', [ShanSongController::class, 'orderInfo']));
        // 查询闪送员位置信息
        Route::get('courier_info/:id', implode('@', [ShanSongController::class, 'courierInfo']));
    })->middleware(LockerMiddleware::class, LockerParameters::builderUid());

    // 腾讯地图
    Route::group('tencent_map', function () {
        // 逆地址解析（坐标位置描述）
        Route::any('location2address', implode('@', [TencentMapController::class, 'location2address']))->middleware(LimiterMiddleware::class, ['limit' => 3, 'window' => 5]);
    });
})->middleware(AllowOriginMiddleware::class)
    ->middleware(StationOpenMiddleware::class)
    ->middleware(AuthTokenMiddleware::class, true)
    ->option(['mark' => 'intra_city_api', 'mark_name' => '移动端同城配送']);