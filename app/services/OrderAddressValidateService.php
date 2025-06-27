<?php

namespace Ledc\CrmebIntraCity\services;

use app\model\user\UserAddress;
use app\services\order\StoreOrderCartInfoServices;
use Ledc\CrmebIntraCity\enums\TransOrderStatusEnums;
use Ledc\CrmebIntraCity\model\EbStoreOrderChangeAddress;
use Ledc\CrmebIntraCity\ServiceTransEnums;
use Ledc\CrmebIntraCity\ShanSongHelper;
use think\exception\ValidateException;

/**
 * 订单地址变更验证服务
 */
class OrderAddressValidateService extends OrderAddressService
{
    /**
     * 验证变更地址
     * @param UserAddress $userAddress
     * @return EbStoreOrderChangeAddress
     * @throws ValidateException
     */
    public function validateChangeAddress(UserAddress $userAddress): EbStoreOrderChangeAddress
    {
        $storeOrder = $this->getStoreOrder();
        if ($storeOrder->change_user_address_id) {
            throw new ValidateException('当前订单已提交变更地址申请');
        }

        $change_data = $this->extractUpdatingOrderData($userAddress);
        if ($this->isSameAddress($change_data)) {
            throw new ValidateException('收货地址与当前订单一致');
        }

        if (!TransOrderStatusEnums::isAllowChangeAddressOrExpectedFinishedTime($storeOrder->trans_order_status)) {
            throw new ValidateException('订单已呼叫配送员，请联系客服');
        } else {
            // 预估配送费
            // 与订单配送费比较，判断用户是否需要补运费差价
            // 返回申请结果与补运费差价
            $wechat_service_trans_id = $storeOrder->wechat_service_trans_id ?: ServiceTransEnums::TRANS_SHANSONG;
            switch ($wechat_service_trans_id) {
                case ServiceTransEnums::TRANS_SHANSONG:
                    $model = $this->validateChangeAddressByShansong($change_data, $userAddress);
                    break;
                case ServiceTransEnums::TRANS_SFTC:
                case ServiceTransEnums::TRANS_DADA:
                default:
                    $model = $this->validateChangeAddressByWechat($change_data, $userAddress);
                    break;
            }
            // 更新订单
            $storeOrder->change_user_address_id = $model->id;
            $storeOrder->save();

            return $model;
        }
    }

    /**
     * 验证闪送变更收货信息
     * @param array $change_data
     * @param UserAddress $userAddress
     * @return EbStoreOrderChangeAddress
     */
    private function validateChangeAddressByShansong(array $change_data, UserAddress $userAddress): EbStoreOrderChangeAddress
    {
        $storeOrder = $this->getStoreOrder();
        $model = EbStoreOrderChangeAddress::makeChangeAddress($storeOrder, $userAddress);

        if ($this->isSameAddress($change_data, ['real_name', 'user_phone']) && false === $this->isSamePhone($storeOrder->user_phone, $change_data['user_phone'])) {
            // 场景：变更手机号
            $model->setPaidStatus(true);
            $model->change_reason = '仅变更手机号，无需补差价';
        } else {
            // 场景：变更地址
            $shansongService = new ShanSongService();
            // 预估配送费
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $cartInfo = $cartServices->getCartColunm(['oid' => $storeOrder->id], 'cart_num,surplus_num,cart_info,refund_num', 'unique');
            $orderCalculateResponse = $shansongService->cartInfoCalculate($cartInfo, $userAddress, $storeOrder->expected_finished_time);
            $storePostage = $orderCalculateResponse->totalFeeAfterSave ? bcdiv((string)$orderCalculateResponse->totalFeeAfterSave, '100', '2') : '0';
            $seller_freight_limit = ShanSongHelper::getSellerFreightLimit();
            /**
             * 运费低于10元，商家包邮
             * 运费高于10元，商家承担10元运费，其他运费客户承担
             */
            $storePostage = $storePostage > $seller_freight_limit ? $storePostage - $seller_freight_limit : 0;
            // 与订单配送费比较，判断用户是否需要补运费差价
            $diff_price = bcsub((string)$storePostage, (string)$storeOrder->pay_postage, 2);
            switch (bccomp((string)$diff_price, '0', 2)) {
                case 0:
                    $model->setPaidStatus(true);
                    $model->change_reason = '无需补差价，请等待客服审核';
                    break;
                case -1:
                    $model->setPaidStatus(true);
                    $model->change_diff_price = $diff_price;
                    $model->change_reason = '请联系客服，退运费差价：' . abs($diff_price) . '元';
                    break;
                case 1:
                    $model->pay_price = $diff_price;
                    $model->change_diff_price = $diff_price;
                    $model->change_reason = '变更收货信息，需补差价：' . $diff_price;
                    break;
            }
        }
        $model->save();

        return $model;
    }

    /**
     * 验证微信同城配送变更收货信息
     * @param array $change_data
     * @param UserAddress $userAddress
     * @return EbStoreOrderChangeAddress
     */
    private function validateChangeAddressByWechat(array $change_data, UserAddress $userAddress): EbStoreOrderChangeAddress
    {
        // throw new ValidateException('暂不支持微信同城配送变更收货信息');
        // todo...
    }
}