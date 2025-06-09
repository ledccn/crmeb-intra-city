<?php

namespace Ledc\CrmebIntraCity\parameters;

use InvalidArgumentException;
use Ledc\CrmebIntraCity\model\EbPricingIncreaseStrategy;
use Ledc\ShanSong\Enums\AppointTypeEnums;
use Ledc\ShanSong\Parameters\OrderCalculate;
use Ledc\ShanSong\Parameters\OrderCalculateReceiver;

/**
 * 创建闪送订单的附加参数
 * - 闪送快速通道费
 * - 闪送指定交通工具
 * - 闪送预约配送服务（预约单+预约取件时间）
 * - 闪送尊享送服务（货品类型+蛋糕尺寸）
 * - 闪送保价服务（投保+投保金额）
 * - 闪送智能订单加价
 */
final class ShanSongParameters extends Parameters
{
    /**
     * 预约类型
     * - 0立即单，1预约单
     * @var int
     */
    public int $appointType = AppointTypeEnums::IMMEDIATELY;
    /**
     * 预约取件时间
     * - yyyy-MM-dd HH:mm格式(例如：2020-02-02 22:00）,指的是预约取件时间,只支持一个小时以后两天以内
     * @var string
     */
    public string $appointmentDate = '';
    /**
     * 指定交通工具
     * - 通过查询城市可指定交通方式接口获取对应travelWay字段，指定交通工具会产生交通费，默认为0：不限交通方式；
     * @var int
     */
    public int $travelWay = 0;
    /**
     * 快速通道费
     * - 单位为分，能被100整除，最大值为10000，用于促进闪送员接单
     * @var string
     */
    public string $additionFee = '';
    /**
     * 物品类型
     * - 1-文件,3-数码,5-蛋糕,6-餐饮,7-鲜花,9-汽配,10-其他,12-母婴,13-医药健康,15-商超,16-水果
     * @var int|null
     */
    public ?int $goodType = null;
    /**
     * 尊享送服务
     * - 1：使用尊享送服务
     * @var int|null
     */
    public ?int $qualityDelivery = null;
    /**
     * 蛋糕尺寸
     * - 当qualityDelivery为1，并且goodType为5时，必传。详见下方蛋糕尺寸枚举
     * @var int|null
     */
    public ?int $goodsSizeId = null;
    /**
     * 是否投保:非必填
     * - 0:不投保;1:投保，默认值为0。投保金额以goodsPrice为准。
     * @var int
     */
    public int $insuranceFlag = 0;
    /**
     * 投保金额，单位：分
     * - insuranceFlag为1时，必传。闪送会根据投保金额计算保险费用，如果你的物品破损或丢失，将可根据投保金额进行索赔
     * @var int|null
     */
    public ?int $goodsPrice = null;
    /**
     * 同城配送加价策略
     * @var array|null|EbPricingIncreaseStrategy
     */
    public ?array $pricingIncreaseStrategy = [];

    /**
     * 构造函数
     * @param array $properties
     * @return void
     */
    public function __construct(array $properties = [])
    {
        if (!empty($properties)) {
            // 前端传过来的是布尔值，需要转换成数字
            $properties['qualityDelivery'] = isset($properties['qualityDelivery']) && $properties['qualityDelivery'] ? 1 : null;
            $properties['insuranceFlag'] = isset($properties['insuranceFlag']) && $properties['insuranceFlag'] ? 1 : 0;
            $this->setExists(true);
            $this->initProperties($properties);
            $this->validate($properties);
        }
    }

    /**
     * 验证参数
     * @param array $properties
     * @return void
     */
    protected function validate(array $properties): void
    {
        // 预约单不支持尊享送服务
        if (AppointTypeEnums::APPOINTMENT === $this->appointType && !empty($this->qualityDelivery)) {
            throw new InvalidArgumentException('预约单不支持尊享送服务');
        }
        // 验证预约取件时间
        OrderCalculate::validateAppointment($this->appointType, $this->appointmentDate);
        // 验证尊享服务，蛋糕尺寸
        OrderCalculateReceiver::validateGoodsSizeIde($this->qualityDelivery, $this->goodType, $this->goodsSizeId);
    }

    /**
     * 静态构造函数
     * @param array $properties
     * @return self
     */
    public static function make(array $properties): self
    {
        return new self($properties);
    }

    /**
     * 获取缓存的key
     * @return string
     */
    public function getCacheKey(): string
    {
        return 'ShanSongParameters_' . $this->getStoreOrder()->id;
    }

    /**
     * 获取必填参数
     * @return array
     */
    protected function getRequiredKeys(): array
    {
        return [];
    }
}
