<?php
/**
 * @Filename CowCoinActivityRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */
namespace ShopEM\Repositories;
use Carbon\Carbon;
use ShopEM\Models\UserCowCoinLog;

class CowCoinActivityRepository
{


    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [ ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'before_gm_id', 'title' => '项目名称'],
            ['dataIndex' => 'mobile', 'title' => '手机号'],
            ['dataIndex' => 'before_point', 'title' => '兑换前积分数'],
            ['dataIndex' => 'before_cowcoin', 'title' => '兑换前牛币数'],
            ['dataIndex' => 'point', 'title' => '使用积分'],
            ['dataIndex' => 'cowcoin', 'title' => '兑成牛币'],
            ['dataIndex' => 'created_at', 'title' => '兑换时间'],
            ['dataIndex' => 'after_point', 'title' => '兑换后积分数'],
            ['dataIndex' => 'after_cowcoin', 'title' => '兑换后牛币数'],
            ['dataIndex' => 'parities', 'title' => '牛币兑换比例'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 积分转牛币列表
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function lists($request)
    {
        $model = new UserCowCoinLog();
        $orderby = 'user_cow_coin_logs.created_at';
        $direction = 'desc';
        if (isset($request['orderby']) )
        {
            $orderby = $request['orderby'];
            unset($request['orderby']);
        }
        if (isset($request['direction']) && in_array($request['direction'], ['desc', 'asc']))
        {
            $direction = $request['direction'];
            unset($request['direction']);
        }
        $model = filterModel($model, $this->filterables, $request);
        if(!isset($request['mobile'])){
            $lists = $model->where('after_gm_id',$request['gm_id'])->orderBy($orderby, $direction)->paginate($request['per_page']);
        }else{
            $lists = $model->leftJoin('user_accounts','user_accounts.id','=','user_cow_coin_logs.user_id')
                ->where('user_cow_coin_logs.after_gm_id',$request['gm_id'])
                ->where('user_accounts.mobile','like',"%".$request['mobile']."%")
                ->select('user_cow_coin_logs.*')
                ->orderBy($orderby, $direction)
                ->paginate($request['per_page']);
        }


        return $lists;
    }



}