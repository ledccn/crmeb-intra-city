<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\order\StoreOrder;
use app\model\order\StoreOrderStatus;
use app\model\system\store\SystemStore;
use app\model\user\UserAddress;
use app\services\order\StoreOrderCartInfoServices;
use Ledc\CrmebIntraCity\enums\OrderChangeTypeEnums;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\observer\shansong\Subject;
use Ledc\CrmebIntraCity\parameters\ShanSongParameters;
use Ledc\CrmebIntraCity\ServiceTransEnums;
use Ledc\CrmebIntraCity\ShanSongHelper;
use Ledc\IntraCity\Enums\CargoTypeEnums;
use Ledc\ShanSong\Config;
use Ledc\ShanSong\Conversion;
use Ledc\ShanSong\Enums\GoodTypeEnums;
use Ledc\ShanSong\Enums\OrderingSourceTypeEnums;
use Ledc\ShanSong\Merchant;
use Ledc\ShanSong\Parameters\Notify;
use Ledc\ShanSong\Parameters\OrderCalculate;
use Ledc\ShanSong\Parameters\OrderCalculateReceiver;
use Ledc\ShanSong\Parameters\OrderCalculateReceiverList;
use Ledc\ShanSong\Parameters\OrderCalculateResponse;
use Ledc\ShanSong\Parameters\OrderCalculateSender;
use Ledc\ShanSong\Parameters\OrderPlaceResponse;
use Ledc\ThinkModelTrait\RedisLocker;
use think\exception\ValidateException;
use think\facade\Cache;
use think\facade\Event;
use think\facade\Log;
use Throwable;

/**
 * 闪送服务类
 */
class ShanSongService
{
    /**
     * 闪送自营商户
     * @var Merchant
     */
    protected Merchant $merchant;

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->merchant = ShanSongHelper::merchant();
    }

    /**
     * 获取闪送自营商户
     * @return Merchant
     */
    public function getMerchant(): Merchant
    {
        return $this->merchant;
    }

    /**
     * 设置为测试环境调试模式
     * @return void
     */
    protected function setTestEnv(): void
    {
        $this->getConfig()->setDebug(true);
    }

    /**
     * 获取闪送配置
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->getMerchant()->getConfig();
    }

    /**
     * 查询开通城市
     * @return array
     */
    public function openCitiesLists(): array
    {
        $cacheKey = 'shansong_open_cities_lists' . $this->getConfig()->autoShopId();
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        $data = $this->merchant->openCitiesLists();
        Cache::set($cacheKey, $data, 86400);
        return $data;
    }

    /**
     * 分页查询商户店铺
     * @param int $pageNo
     * @param int $pageSize
     * @param string $storeName
     * @param bool $debug
     * @return array
     */
    public function queryAllStores(int $pageNo = 1, int $pageSize = 20, string $storeName = '', bool $debug = false): array
    {
        if ($debug) {
            $this->setTestEnv();
        }
        return $this->merchant->queryAllStores($pageNo, $pageSize, $storeName);
    }

    /**
     * 查询城市可指定的交通工具
     * @param int $cityId 城市ID（查询开通城市接口获取，对应id字段）
     * @return array
     */
    public function optionalTravelWay(int $cityId): array
    {
        $cacheKey = 'shansong_optional_travel_way_' . $this->getConfig()->autoShopId() . $cityId;
        $data = Cache::get($cacheKey);
        if ($data) {
            return $data;
        }

        $data = $this->merchant->optionalTravelWay($cityId);
        Cache::set($cacheKey, $data, 3600);
        return $data;
    }

    /**
     * 从数据库查询店铺提货点
     * @param int $system_store_id CRMEB系统提货点ID
     * @return SystemStore
     */
    public static function getSystemStore(int $system_store_id): SystemStore
    {
        if ($system_store_id) {
            /** @var SystemStore $systemStore */
            $systemStore = SystemStore::findOrEmpty($system_store_id);
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

    /**
     * 构造发件人信息
     * @param SystemStore $systemStore 店铺提货点
     * @return OrderCalculateSender
     */
    protected static function builderSender(SystemStore $systemStore): OrderCalculateSender
    {
        $sender = new OrderCalculateSender();
        $sender->fromAddress = $systemStore->detailed_address;
        $sender->fromAddressDetail = $systemStore->address . ' ' . $systemStore->detailed_address;
        $sender->fromSenderName = $systemStore->getAttr('name');
        $sender->fromMobile = $systemStore->phone;
        $sender->fromLatitude = $systemStore->shansong_latitude;
        $sender->fromLongitude = $systemStore->shansong_longitude;
        return $sender;
    }

    /**
     * 购物车信息配送订单计费
     * @param array $cartInfo
     * @param UserAddress $userAddress
     * @return OrderCalculateResponse
     */
    public function cartInfoCalculate(array $cartInfo, UserAddress $userAddress): OrderCalculateResponse
    {
        // TODO... 根据用户地理位置匹配最佳提货点
        $systemStore = self::getSystemStore(0);
        if ($userAddress->isEmpty()) {
            throw new ValidateException('收货地址为空');
        }

        // 发件人信息
        $sender = self::builderSender($systemStore);

        // 收件人信息
        $receiver = new OrderCalculateReceiver();
        $receiver->orderNo = time();
        $receiver->toAddress = implode(' ', [$userAddress->map_address, $userAddress->map_name, $userAddress->detail]);
        $bd09 = Conversion::GCJ02ToBD09($userAddress->longitude, $userAddress->latitude);
        $receiver->toLatitude = $bd09['latitude'];
        $receiver->toLongitude = $bd09['longitude'];
        $receiver->toReceiverName = $userAddress->real_name;
        $receiver->toMobile = $userAddress->phone;

        $cargo_weight = 0;
        array_map(function ($item) use (&$cargo_weight) {
            $weight = $item['attrInfo']['weight'] ?? '0';
            $cargo_weight = bcadd($cargo_weight, bcmul($item['cart_num'], $weight));
        }, $cartInfo);
        $cargo_type = WechatIntraCityService::getCargoType($cartInfo);

        $receiver->goodType = static::convertEnums($cargo_type);
        $receiver->weight = ceil($cargo_weight) ?: 1;
        $receiver->remarks = '估算配送费';

        // 收件人信息列表
        $receiverList = new OrderCalculateReceiverList();
        $receiverList->add($receiver);

        $orderCalculate = new OrderCalculate();
        $orderCalculate->cityName = $systemStore->shansong_city_name;
        $orderCalculate->storeId = $this->getConfig()->isDebug() ? $systemStore->shansong_store_id_test : $systemStore->shansong_store_id;
        $orderCalculate->deliveryPwd = 1;
        $orderCalculate->sender = $sender;
        $orderCalculate->receiverList = $receiverList;
        log_develop('闪送订单计费参数:  ' . json_encode($orderCalculate, JSON_UNESCAPED_UNICODE));
        return $this->merchant->orderCalculate($orderCalculate);
    }

    /**
     * 订单计费
     * @param StoreOrder $storeOrder
     * @param ShanSongParameters $shanSongParameters
     * @return OrderCalculateResponse
     */
    public function orderCalculate(StoreOrder $storeOrder, ShanSongParameters $shanSongParameters): OrderCalculateResponse
    {
        $systemStore = self::getSystemStore($storeOrder->store_id);

        $user_address_object = $storeOrder->user_address_object;
        if (empty($user_address_object)) {
            // 虚拟商品不能喊骑手发货
            throw new ValidateException('用户地址表对象为空');
        }

        // 发件人信息
        $sender = self::builderSender($systemStore);

        // 收件人信息
        $receiver = new OrderCalculateReceiver();
        $receiver->orderNo = $storeOrder->order_id;
        $receiver->toAddress = $storeOrder->user_address;
        $bd09 = Conversion::GCJ02ToBD09($storeOrder->user_lng, $storeOrder->user_lat);
        $receiver->toLatitude = $bd09['latitude'];
        $receiver->toLongitude = $bd09['longitude'];
        $receiver->toReceiverName = $storeOrder->real_name;
        $receiver->toMobile = $storeOrder->user_phone;
        if ($storeOrder->paid && $storeOrder->pay_time && $storeOrder->order_seq) {
            $receiver->orderingSourceType = OrderingSourceTypeEnums::INT_1;
            $receiver->orderingSourceNo = date('md', $storeOrder->pay_time) . str_pad($storeOrder->order_seq, 4, '0', STR_PAD_LEFT);
        }
        // 期望送达时间
        if ($storeOrder->expected_finished_start_time && $storeOrder->expected_finished_end_time) {
            $expected_finished_start_time = strtotime($storeOrder->expected_finished_start_time);
            $expected_finished_end_time = strtotime($storeOrder->expected_finished_end_time);
            if (time() < $expected_finished_start_time && $expected_finished_start_time < $expected_finished_end_time) {
                $receiver->expectStartTime = $expected_finished_start_time * 1000;
                $receiver->expectEndTime = $expected_finished_end_time * 1000;
            }
        }

        $orderCalculate = new OrderCalculate();
        $orderCalculate->cityName = $systemStore->shansong_city_name;
        $orderCalculate->storeId = $this->getConfig()->isDebug() ? $systemStore->shansong_store_id_test : $systemStore->shansong_store_id;
        $orderCalculate->deliveryPwd = 1;
        $orderCalculate->sender = $sender;

        // 创建闪送订单的附加参数
        if ($shanSongParameters->isExists()) {
            $orderCalculate->appointType = $shanSongParameters->appointType;
            $orderCalculate->appointmentDate = $shanSongParameters->appointmentDate;
            $orderCalculate->travelWay = $shanSongParameters->travelWay;
            $receiver->additionFee = $shanSongParameters->additionFee;
            $receiver->qualityDelivery = $shanSongParameters->qualityDelivery;
            $receiver->goodsSizeId = $shanSongParameters->goodsSizeId;
            $receiver->insuranceFlag = $shanSongParameters->insuranceFlag;
            $receiver->goodsPrice = $shanSongParameters->goodsPrice;
        }

        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $cartServices->getCartColunm(['oid' => $storeOrder->id], 'cart_num,surplus_num,cart_info,refund_num', 'unique');
        $cargo_weight = 0;
        array_map(function ($item) use (&$cargo_weight) {
            $row = is_array($item['cart_info']) ? $item['cart_info'] : json_decode($item['cart_info'], true);
            $weight = $row['attrInfo']['weight'] ?? '0';
            $cargo_weight = bcadd($cargo_weight, bcmul($row['cart_num'], $weight));
        }, $cartInfo);
        $cargo_type = WechatIntraCityService::getCargoType($cartInfo);
        $good_type = static::convertEnums($cargo_type);

        $receiver->goodType = $shanSongParameters->goodType ?: $good_type;
        $receiver->weight = ceil($cargo_weight) ?: 1;
        $receiver->remarks = '流水号：' . date('md', $storeOrder->pay_time) . str_pad($storeOrder->order_seq, 4, '0', STR_PAD_LEFT);
        $orderCalculate->receiverList = (new OrderCalculateReceiverList())->add($receiver);
        log_develop('闪送订单计费构造参数: ' . json_encode($orderCalculate, JSON_UNESCAPED_UNICODE));
        return $this->merchant->orderCalculate($orderCalculate);
    }

    /**
     * 转换枚举
     * @param int $cargoType
     * @return int|mixed
     */
    public static function convertEnums(int $cargoType)
    {
        $enums = [
            CargoTypeEnums::INT_1 => GoodTypeEnums::INT_6,
            CargoTypeEnums::INT_2 => GoodTypeEnums::INT_13,
            CargoTypeEnums::INT_3 => GoodTypeEnums::INT_15,
            CargoTypeEnums::INT_6 => GoodTypeEnums::INT_15,
            CargoTypeEnums::INT_8 => GoodTypeEnums::INT_15,
            CargoTypeEnums::INT_12 => GoodTypeEnums::INT_1,
            CargoTypeEnums::INT_13 => GoodTypeEnums::INT_5,
            CargoTypeEnums::INT_14 => GoodTypeEnums::INT_7,
            CargoTypeEnums::INT_15 => GoodTypeEnums::INT_3,
            CargoTypeEnums::INT_16 => GoodTypeEnums::INT_15,
            CargoTypeEnums::INT_17 => GoodTypeEnums::INT_9,
            CargoTypeEnums::INT_32 => GoodTypeEnums::INT_15,
            CargoTypeEnums::INT_56 => GoodTypeEnums::INT_12,
            CargoTypeEnums::INT_99 => GoodTypeEnums::INT_10,
        ];
        return $enums[$cargoType] ?? GoodTypeEnums::INT_10;
    }

    /**
     * 提交订单
     * @param StoreOrder $storeOrder
     * @param ShanSongParameters $shanSongParameters
     * @return OrderPlaceResponse
     */
    public function orderPlace(StoreOrder $storeOrder, ShanSongParameters $shanSongParameters): OrderPlaceResponse
    {
        try {
            CreateOrderValidate::beforeValidate($storeOrder);

            $orderCalculateResponse = $this->orderCalculate($storeOrder, $shanSongParameters);
            $orderPlaceResponse = $this->merchant->orderPlace($orderCalculateResponse->orderNumber);

            // 记录订单变更日志
            StoreOrderStatus::create([
                'oid' => $storeOrder->id,
                'change_type' => OrderChangeTypeEnums::CITY_CREATE_ORDER,
                'change_time' => time(),
                'change_message' => '呼叫同城配送，运力：' . ServiceTransEnums::TRANS_SHANSONG . ' 运力订单号：' . $orderPlaceResponse->orderNumber,
            ]);

            // 更新订单表
            $storeOrder->db()->transaction(function () use ($storeOrder, $orderPlaceResponse) {
                $storeOrder->wechat_wx_store_id = $this->getConfig()->autoShopId();
                $storeOrder->wechat_wx_order_id = $orderPlaceResponse->orderNumber;
                $storeOrder->wechat_service_trans_id = ServiceTransEnums::TRANS_SHANSONG;
                $storeOrder->wechat_distance = $orderPlaceResponse->totalDistance;
                $storeOrder->wechat_trans_order_id = $orderPlaceResponse->orderNumber;
                $storeOrder->wechat_waybill_id = $orderPlaceResponse->orderNumber;
                $storeOrder->wechat_fee = $orderPlaceResponse->totalFeeAfterSave;
                $storeOrder->wechat_fetch_code = '';
                $storeOrder->wechat_processed = 1;
                $storeOrder->trans_order_status = TransOrderStatusEnums::Assigned;
                $storeOrder->trans_order_create_time = time();
                $storeOrder->trans_order_update_time = time();
                $storeOrder->save();
            });

            return $orderPlaceResponse;
        } catch (Throwable $throwable) {
            Log::error('闪送提交订单异常:' . $throwable->getMessage());
            throw new ValidateException($throwable->getMessage());
        }
    }

    /**
     * 订单加价
     * @param StoreOrder $storeOrder
     * @param int $additionAmount
     * @return array
     */
    public function addition(StoreOrder $storeOrder, int $additionAmount): array
    {
        return $this->merchant->addition($storeOrder->wechat_trans_order_id, $additionAmount);
    }

    /**
     * 查询订单详情
     * @param StoreOrder $storeOrder
     * @return array
     */
    public function orderInfo(StoreOrder $storeOrder): array
    {
        $data = $this->merchant->orderInfo($storeOrder->wechat_trans_order_id, $storeOrder->order_id);
        // 闪送员信息
        $courier = $data['courier'] ?? [];
        if (!empty($courier)) {
            $latitude = $courier['latitude'] ?? null;
            $longitude = $courier['longitude'] ?? null;
            if ($latitude && $longitude) {
                // 转换为GCJ02
                $gcj02 = Conversion::BD09ToGCJ02($longitude, $latitude);
                $courier['_latitude'] = $gcj02['latitude'];
                $courier['_longitude'] = $gcj02['longitude'];
                // 添加GCJ02坐标
                $data['courier'] = $courier;
            }
        }
        return $data;
    }

    /**
     * 查询闪送员位置信息
     * @param StoreOrder $storeOrder
     * @return array
     */
    public function courierInfo(StoreOrder $storeOrder): array
    {
        $data = $this->merchant->courierInfo($storeOrder->wechat_trans_order_id);
        $longitude = $data['longitude'] ?? null;
        $latitude = $data['latitude'] ?? null;
        if ($longitude && $latitude) {
            // 转换为GCJ02
            $gcj02 = Conversion::BD09ToGCJ02($longitude, $latitude);
            // 添加GCJ02坐标
            $data['_latitude'] = $gcj02['latitude'];
            $data['_longitude'] = $gcj02['longitude'];
        }
        return $data;
    }

    /**
     * 查询订单续重加价金额
     * @param StoreOrder $storeOrder
     * @param string $weight
     * @return array
     */
    public function calculateOrderAddWeightFee(StoreOrder $storeOrder, string $weight): array
    {
        return $this->merchant->calculateOrderAddWeightFee($storeOrder->wechat_trans_order_id, $weight);
    }

    /**
     * 支付订单续重费用
     * @param StoreOrder $storeOrder
     * @param string $payAmount 支付金额（单位：分）
     * @param string $weight 重量（单位：kg）
     * @return array
     */
    public function payAddWeightFee(StoreOrder $storeOrder, string $payAmount, string $weight): array
    {
        return $this->merchant->payAddWeightFee($storeOrder->wechat_trans_order_id, $payAmount, $weight);
    }

    /**
     * 订单预取消
     * @param StoreOrder $storeOrder
     * @return array
     */
    public function preAbortOrder(StoreOrder $storeOrder): array
    {
        return $this->merchant->preAbortOrder($storeOrder->wechat_trans_order_id);
    }

    /**
     * 订单取消
     * @param StoreOrder $storeOrder
     * @param bool $deductFlag 是否同意扣除余额  true:同意，false:不同意，默认false
     * @return array
     */
    public function abortOrder(StoreOrder $storeOrder, bool $deductFlag = false): array
    {
        return $this->merchant->abortOrder($storeOrder->wechat_trans_order_id, $deductFlag);
    }

    /**
     * 确认物品送回
     * @param StoreOrder $storeOrder
     * @return array
     */
    public function confirmGoodsReturn(StoreOrder $storeOrder): array
    {
        return $this->merchant->confirmGoodsReturn($storeOrder->wechat_trans_order_id);
    }

    /**
     * 查询账号额度
     * @return array
     */
    public function getUserAccount(): array
    {
        return $this->merchant->getUserAccount();
    }

    /**
     * 修改收件人手机号
     * @param StoreOrder $storeOrder
     * @param string $newToMobile 新收件人手机号
     * @return array
     */
    public function updateToMobile(StoreOrder $storeOrder, string $newToMobile): array
    {
        return $storeOrder->db()->transaction(function () use ($storeOrder, $newToMobile) {
            $result = [];
            if ($storeOrder->wechat_service_trans_id === ServiceTransEnums::TRANS_SHANSONG && $storeOrder->wechat_processed) {
                $result = $this->merchant->updateToMobile($storeOrder->wechat_trans_order_id, $storeOrder->order_id, $newToMobile);
            }

            $storeOrder->user_phone = $newToMobile;
            $storeOrder->save();
            return $result;
        });
    }

    /**
     * 查询订单ETA
     * @return array
     */
    public function orderEta(): array
    {
        return [];
    }

    /**
     * 订单追单
     * @return array
     */
    public function appendOrder(): array
    {
        return [];
    }

    /**
     * 查询是否支持尊享送
     * @param string $cityName
     * @return array
     */
    public function qualityDeliverySwitch(string $cityName): array
    {
        return $this->merchant->qualityDeliverySwitch($cityName);
    }

    /**
     * 查询尊享送达成状态
     * @param StoreOrder $storeOrder
     * @return array
     */
    public function qualityDeliveryStatus(StoreOrder $storeOrder): array
    {
        return $this->merchant->qualityDeliveryStatus($storeOrder->wechat_trans_order_id);
    }

    /**
     * 订单状态回调
     * - 回调返回的格式必须是{"status":200,"msg":"","data":null}格式，当status为200表示回调成功，否则，回调失败。当回调失败时，间隔一分钟重试一次，最多重试五次。
     * @param Notify $notify
     * @return int
     */
    public function notifyCallback(Notify $notify): int
    {
        $lockKey = implode('_', [$notify->issOrderNo, $notify->orderNo, $notify->status, $notify->subStatus]);
        $locker = new RedisLocker('shansong_callback:' . $lockKey, 10);
        if (!$locker->acquire()) {
            return 429;
        }

        $this->getConfig()->verifyNotify($notify);

        // 调度事件
        Event::trigger(Notify::class, $notify);

        // 验证issOrderNo、orderNo
        /** @var StoreOrder $storeOrder */
        $storeOrder = StoreOrder::where('order_id', '=', $notify->orderNo)->findOrEmpty();
        if ($storeOrder->isEmpty()) {
            throw new ValidateException('订单不存在');
        }
        if ($storeOrder->wechat_trans_order_id !== $notify->issOrderNo) {
            throw new ValidateException('闪送订单号校验失败');
        }

        $subject = new Subject($notify);
        $subject->setStoreOrder($storeOrder);
        $subject->notify();

        return 200;
    }
}
