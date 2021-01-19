<?php
/**
 * @Filename DepartmentRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Department;
use ShopEM\Models\TradeEstimates;
use ShopEM\Models\TradeRewards;
use ShopEM\Models\UserAccount;

class DepartmentRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'name'             => ['field' => 'name', 'operator' => '='],
        'is_show'          => ['field' => 'is_show', 'operator' => '='],
        'created_start_at' => ['field' => 'created_at', 'operator' => '>='],
        'created_end_at'   => ['field' => 'created_at', 'operator' => '<='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'dataIndex' => 'name', 'title' => '部门名称'],
            ['key' => 'note', 'dataIndex' => 'note', 'title' => '部门描述'],
            ['key' => 'is_show_text', 'dataIndex' => 'is_show_text', 'title' => '是否显示'],
            ['key' => 'listorder', 'dataIndex' => 'listorder', 'title' => '排序'],
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
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function listItems($request = [])
    {
        $model = new Department();

        $model = filterModel($model, $this->filterables, $request);
        return $model->orderBy('listorder')->paginate(config('app.per_page'));
    }


    /**
     * 分销团队销售排行榜汇总查询字段
     * @Author hfh_wind
     * @return array
     */
    public function GroupCollectlistFields()
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'name', 'dataIndex' => 'name', 'title' => '部门名称'],
            ['key' => 'count_son', 'dataIndex' => 'count_son', 'title' => '推荐人员'],
            ['key' => 'estimated_value', 'dataIndex' => 'estimated_value', 'title' => '分销金额'],
            ['key' => 'rewards_count', 'dataIndex' => 'rewards_count', 'title' => '提成金额'],
        ];
    }

    /**
     * 获取分销团队销售排行榜汇总数据显示字段
     * @Author hfh_wind
     * @return array
     */
    public function GroupCollectShowFields()
    {
        return listFieldToShow($this->GroupCollectlistFields());
    }


    /**
     * 获取分销团队销售排行榜汇总数据
     * @Author hfh_wind
     * @param array $request
     * @return mixed
     */
    public function GroupCollectSearch($request = [])
    {
        $model = new Department();

//        $model = filterModel($model, $this->filterables, $request);

        $lists = $model->orderBy('listorder', 'desc')->get();

        if (count($lists) > 0) {

//            $lists = $lists->toArray();

            foreach ($lists as $key => $value) {

//->where('created_at','>=',$request['created_start_at'])->where('created_at','<=',$request['created_end_at'])

//                $res=ApplyPromoter::where('department_id',$value['id'])->get();
                $res = DB::table('apply_promoters')
                    ->where('department_id', $value['id'])
                    ->get();

                if (count($res) > 0) {
                    foreach ($res as $res_value) {

                        $tradeEstimates=new TradeEstimates();
                        if (isset($request['created_start_at'])) {
                            $tradeEstimates=$tradeEstimates->whereDate('created_at','>=',$request['created_start_at'])->whereDate('created_at','<=',$request['created_end_at']);
                        }

                        $estimated_count_all=$tradeEstimates->where('pid',$res_value->user_id)->where('status', 0)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct oid) as count ')->first();

                        if($estimated_count_all){
                            $lists[$key]['estimated_value'] += $estimated_count_all['reward_value'];
                        }

                        $tradeRewards=new  TradeRewards();
                        if (isset($request['created_start_at'])) {
                            $tradeRewards=$tradeRewards->where('created_at','>=',$request['created_start_at'])->where('created_at','<=',$request['created_end_at']);
                        }
                        $tradeRewards=$tradeRewards->where('pid',$res_value->user_id)->selectRaw('IFNULL(sum(reward_value),0) as reward_value,count(distinct oid) as count ')->first();
                        if($tradeRewards){
                            $lists[$key]['reward_value'] += $tradeRewards['reward_value'];
                        }

                        $countSon=UserAccount::where('pid', $res_value->user_id)->count();
                        $lists[$key]['count_son'] += $countSon;
                    }
                } else {
                    $lists[$key]['estimated_value'] = 0;
                    $lists[$key]['reward_value'] = 0;
                    $lists[$key]['count_son'] = 0;
                }
            }

//            dd($lists);
            $lists = $lists->toArray();

            //根据字段estimated_value对数组 $lists['data'] 进行降序排列
            $estimated_value = array_column($lists, 'estimated_value');
            array_multisort($estimated_value, SORT_DESC, $lists);
        }

        return $lists;
    }


    /**
     * 获取所有的部门数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function allItems()
    {
        return Cache::remember('all_show_departmenta', cacheExpires(), function () {
            return Department::orderBy('listorder')->where('is_show', 1)->get();
        });
    }
}