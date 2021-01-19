<?php
/**
 * @Filename        UserAccountRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\UVTrade;

class UVStatisticsRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'trading_day_start'  => ['field' => 'trading_day', 'operator' => '>='],
        'trading_day_end'  => ['field' => 'trading_day', 'operator' => '<='],
    ];


    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields($is_show='')
    {
        return
        [
            ['dataIndex' => 'gm_name', 'title' => '项目名'],
            ['dataIndex' => 'shop_name', 'title' => '店铺名'],
            ['dataIndex' => 'trading_day', 'title' => '统计日期'],
            ['dataIndex' => 'trading_volume', 'title' => '交易人数'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function search($request,$downData='')
    {
        $model = new UVTrade();
        $model = filterModel($model, $this->filterables, $request);

        if($downData)
        {
            //下载提供数据
            $lists = $model->orderBy('id', 'desc')->get();
        }else{
            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
        }

        return $lists;
    }


}
