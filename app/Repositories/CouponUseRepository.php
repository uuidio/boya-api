<?php

/**
 * CouponUseRepository.php
 * @Author: Huiho
 * @Date:   2020-05-06 10:46:30
 */
namespace ShopEM\Repositories;

use ShopEM\Models\CouponUse;

class CouponUseRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'coupon_stock_onlines.gm_id', 'operator' => '='],
        'scenes' => ['field' => 'coupon_stock_onlines.scenes', 'operator' => '='],//券场景
//        'is_hand_push' => ['field' => 'is_hand_push', 'operator' => '='],//是否手动
        'status' => ['field' => 'coupon_stock_onlines.status', 'operator' => '='],//状态
//        'user_mobile' => ['field' => 'user_mobile', 'operator' => '='],
//        'source_shop_id' => ['field' => 'source_shop_id', 'operator' => '='],
        'created_start'  => ['field' => 'coupon_stock_onlines.created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'coupon_stock_onlines.created_at', 'operator' => '<='],
        'used_at_start'  => ['field' => 'coupon_stock_onlines.updated_at', 'operator' => '>='],
        'used_at_end'  => ['field' => 'coupon_stock_onlines.updated_at', 'operator' => '<='],
        'payment_id'  => ['field' => 'coupon_stock_onlines.payment_id', 'operator' => '='],
        'shop_id'  => ['field' => 'coupons.shop_id', 'operator' => '='],

    ];




    /**
     * 查询字段
     *
     * @Author Huiho
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => 'id'],
            ['dataIndex' => 'source_type', 'title' => '发行渠道'],
            ['dataIndex' => 'coupon_name', 'title' => '券名称'],
            ['dataIndex' => 'coupon_id', 'title' => '券ID'],
            ['dataIndex' => 'scenes_text', 'title' => '券场景'],
            ['dataIndex' => 'is_hand_push', 'title' => '是否手动'],
            ['dataIndex' => 'nickname', 'title' => '领取人'],
            ['dataIndex' => 'created_at', 'title' => '领取时间'],
            ['dataIndex' => 'mobile', 'title' => '领取手机号'],
            ['dataIndex' => 'status_text', 'title' => '状态'],
            ['dataIndex' => 'coupon_code', 'title' => '卷码'],
            ['dataIndex' => 'used_at_text', 'title' => '使用时间/核销时间'],//使用时间
            ['dataIndex' => 'shop_name', 'title' => '核销商家'],
            ['dataIndex' => 'bn', 'title' => '核销码'],
            ['dataIndex' => 'tid', 'title' => '核销订单号'],
            ['dataIndex' => 'payed_text', 'title' => '实付金额'],
            ['dataIndex' => 'trade_no', 'title' => '小票号'],
            ['dataIndex' => 'voucher', 'title' => '凭证'],
            ['dataIndex' => 'remark', 'title' => '备注'],
        ];
    }
    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 获取列表
     *
     * @Author Huiho
     * @param Request $request
     * @param int $page_count
     * @return mixed
     */
    public function listItems($request , $downData='')
    {
        $sum_result = [];
        $sum_data = ['amount'];

        if (isset($request['used_at_start'])||isset($request['used_at_end'])) {
            $request['status'] = '1';
        }

        $couponModel = new CouponUse();
        $couponModel = filterModel($couponModel, $this->filterables, $request);

        $couponModel = $couponModel->leftJoin('coupons','coupons.id','coupon_stock_onlines.coupon_id')->select('coupons.name','coupons.is_hand_push','coupons.shop_id','coupon_stock_onlines.*');
        if (isset($request['coupon_name']) && $request['coupon_name'])
        {
            $couponModel = $couponModel->where(function ($query) use ($request)
            {
                $query->orWhere('coupons.name', 'like', '%'.$request['coupon_name'])
                    ->orWhere('coupons.name', 'like', '%'.$request['coupon_name'].'%')
                    ->orWhere('coupons.name', 'like', $request['coupon_name'].'%');
            });
        }

        if (isset($request['is_hand_push'])) {
            $couponModel = $couponModel->where('coupons.is_hand_push' , $request['is_hand_push']);
        }

        if($downData)
        {
            //下载提供数据
            $lists = $couponModel->orderBy('coupon_stock_onlines.id', 'desc')->get();
        }
        else
        {
            $search_model = $couponModel->get();
            $lists = $couponModel->orderBy('coupon_stock_onlines.id', 'desc')->paginate($request['per_page']);
        }


        if ($lists)
        {
            $lists = $lists->toArray();
        }

        $result = self::formatData($lists , $downData);

        if (isset($request['total_data_status']) && $request['total_data_status'])
        {
            $sum_result = $this->totalData($search_model,$sum_data);

            $result['total_fee_data'] =  [
                ['value' => $sum_result['amount'], 'dataIndex' => 'amount', 'title' => '商品实付汇总'],
            ];
        }

        return $result;
    }


    /**
     * 格式化数据
     *
     * @Author Huiho
     * @param Request $request
     * @return mixed
     */
    private function formatData($lists , $downData)
    {

        if($downData)
        {
            //下载提供数据
            foreach ($lists as $key=> &$value)
            {
                //平铺会员信息
                $value['nickname'] = $value['user_accounts_info']['nickname'];
                $value['mobile'] = $value['user_accounts_info']['mobile'];
                unset($value['user_accounts_info']);
                //平铺优惠卷信息
                $value['is_hand_push'] = $value['coupons_info']['is_hand_push'];
                $value['source_type'] = $value['coupons_info']['source_type'];
                $value['coupon_name'] = $value['coupons_info']['coupon_name'];
                unset($value['coupons_info']);

                //平铺核销信息
                $value['bn'] = $value['coupon_stocks_info']['bn'];
                $value['trade_no'] = $value['coupon_stocks_info']['trade_no'];
                $value['remark'] = $value['coupon_stocks_info']['remark'];
                //$value['write_off_at'] = $value['coupon_stocks_info']['write_off_at'];
                $value['voucher'] = $value['coupon_stocks_info']['voucher'];
                unset($value['coupon_stocks_info']);
            }

        }
        else
        {
            foreach ($lists['data'] as $key=> &$value)
            {
                //平铺会员信息
                $value['nickname'] = $value['user_accounts_info']['nickname'];
                $value['mobile'] = $value['user_accounts_info']['mobile'];
                unset($value['user_accounts_info']);
                //平铺优惠卷信息
                $value['is_hand_push'] = $value['coupons_info']['is_hand_push'];
                $value['source_type'] = $value['coupons_info']['source_type'];
                $value['coupon_name'] = $value['coupons_info']['coupon_name'];
                unset($value['coupons_info']);

                //平铺核销信息
                $value['bn'] = $value['coupon_stocks_info']['bn'];
                $value['trade_no'] = $value['coupon_stocks_info']['trade_no'];
                $value['remark'] = $value['coupon_stocks_info']['remark'];
                //$value['write_off_at'] = $value['coupon_stocks_info']['write_off_at'];
                $value['voucher'] = $value['coupon_stocks_info']['voucher'];
                $value['shop_name'] = $value['coupon_stocks_info']['shop_name'];
                unset($value['coupon_stocks_info']);

            }
        }

        return $lists;

    }


    /**
     * 统计方法
     *
     * @Author Huiho
     * @param $request
     * @return mixed
     */
    public function totalData($model,$sum_data)
    {
        $result = [];
        foreach ($sum_data as $key =>&$value)
        {
            //$result[$value] = $model->leftJoin('payments','payments.payment_id','=','coupon_stock_onlines.payment_id')->sum('amount');
            $result[$value] = $model->sum('payed_text');
            $result[$value] = round($result[$value],2);
        }

        return $result;
    }

}