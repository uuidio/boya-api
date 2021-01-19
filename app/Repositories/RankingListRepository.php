<?php
/**
 * @Filename        RankingListRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use Illuminate\Support\Facades\DB;
use ShopEM\Models\ApplyPromoter;
use ShopEM\Models\TradeEstimates;
use ShopEM\Models\TradeOrder;


class RankingListRepository
{


    /**
     * 分销个人-销售排行榜:定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'id'               => ['field' => 'id', 'operator' => '='],
        'user_id'          => ['field' => 'pid', 'operator' => '='],
        'created_start_at' => ['field' => 'created_at', 'operator' => '>='],
        'created_end_at'   => ['field' => 'created_at', 'operator' => '<='],
    ];

    /**
     * 查询字段
     *
     * @Author huiho
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'user_id','dataIndex' => 'user_id', 'title' => '会员ID'],
            ['key' => 'real_name','dataIndex' => 'real_name', 'title' => '真实姓名'],
            ['key' => 'CountSon','dataIndex' => 'CountSon', 'title' => '推荐人员'],
            ['key' => 'amount','dataIndex' => 'amount', 'title' => '分销金额'],
            ['key' => 'reward_value','dataIndex' => 'reward_value', 'title' => '提成金额'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author huiho
     * @return array
     *
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 搜索申请数据
     *
     * @Author huih
     * @param $request
     * @return mixed
     */
    public function userRewardRankingSearch($request)
    {

        $where = '1';
        $trade_where = '1';
        if (isset($request['created_start_at']) && isset($request['created_end_at'])) {
            $created_start_at = $request['created_start_at'];
            $created_end_at = $request['created_end_at'];
            $where = "`created_at` >= '" . $created_start_at . "'  and `created_at` <= '" . $created_end_at . "'";
            $trade_where = "`created_at` >= '" . $created_start_at . "'  and `created_at` <= '" . $created_end_at . "'";

//            $created_start_at=$request['created_start_at'];
//            $created_end_at=$request['created_end_at'];
//            $lists=DB::select("select  SUM(reward_value) as reward_value,pid,GROUP_CONCAT(oid) as oids  FROM  em_trade_estimates WHERE  `created_at` >= '".$created_start_at."'  and `created_at` <= '".$created_end_at."'  GROUP BY pid  ORDER BY  reward_value  DESC");
        }


        if (isset($request['user_id']) && isset($request['user_id'])) {

            $where .= ' and pid =' . $request['user_id'];
            $trade_where .= ' and user_id =' . $request['user_id'];
        }


        $lists = DB::select("select  SUM(reward_value) as reward_value,pid    FROM  em_trade_estimates WHERE   $where   and   status='0'     GROUP BY pid  ORDER BY  reward_value  DESC");


        $data = [];
        foreach ($lists as $key => $value) {

            $data[$key]['reward_value'] = $value->reward_value;
            $res = ApplyPromoter::where('user_id', $value->pid)->first();

            $data[$key]['user_id'] = $res['user_id']??'';
            $data[$key]['real_name'] = $res['real_name']??'';

           
//            //分销金额
//            $seller_price = DB::select("select  SUM(amount) as  amount  FROM  em_trade_orders WHERE $trade_where  and  oid in($oids) ");
            //            $data[$key]['amount'] = $seller_price[0]->amount??0;
            //分销金额
            $count_oid=[];
            $price=TradeEstimates::where(['pid'=>$value->pid,'status' =>0])->get();
            $total=0;
            foreach($price  as $price_key => $price_v){
                if(!in_array($price_v['oid'],$count_oid)){
                    $orderInfo = TradeOrder::where('oid', $price_v['oid'])->first();
                    $count_oid[$price_key]=$price_v['oid'];
                    $total +=$orderInfo['amount'];
                }
            }
            $data[$key]['amount']=round($total,2);

            $count = DB::select("select  COUNT(*) as  total  FROM  em_user_accounts WHERE  $where  and  pid=" . $value->pid . "  ");
            $data[$key]['CountSon'] = $count[0]->total??0;
//            $value=UserAccount::where('pid', $value->user_id)->count();
        }


        return $data;
    }


}