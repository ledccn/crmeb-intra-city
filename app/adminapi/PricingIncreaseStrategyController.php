<?php

namespace Ledc\CrmebIntraCity\adminapi;

use app\Request;
use Ledc\CrmebIntraCity\model\EbPricingIncreaseStrategy;
use Ledc\CrmebIntraCity\validate\PricingIncreaseStrategyValidate;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;

/**
 * 同城配送加价策略
 */
class PricingIncreaseStrategyController
{
    /**
     * 显示资源列表
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index(Request $request): Response
    {
        $model = new EbPricingIncreaseStrategy();
        $query = $model->db();

        return response_json()->success('ok', [
            'list' => $query->select()->toArray(),
            'count' => $query->count(),
        ]);
    }

    /**
     * 保存资源
     * @param Request $request
     * @return Response
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function save(Request $request): Response
    {
        $id = $request->post('id/d', 0);
        $data = $request->postMore([
            'strategy_name',
            'minutes_until_increase',
            'increase_interval_minutes',
            'increase_amount',
            'max_increase_amount',
            'is_active',
        ]);

        validate(PricingIncreaseStrategyValidate::class)->check($data);

        if ($id) {
            $model = EbPricingIncreaseStrategy::findOrFail($id);
            $model->save($data);
        } else {
            $model = EbPricingIncreaseStrategy::create($data);
        }

        return response_json()->success('ok', $model->toArray());
    }

    /**
     * 删除指定资源
     * @method DELETE
     * @param int|string $id
     * @return Response
     */
    public function delete($id): Response
    {
        $ids = is_array($id) ? $id : explode('_', $id);
        EbPricingIncreaseStrategy::destroy($ids);
        return response_json()->success('ok');
    }
}