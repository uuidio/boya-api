<?php


namespace ShopEM\Repositories;


use ShopEM\Models\TradeStockReturnLog;

class TradeStockReturnLogRepository
{
    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterables = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
        'status' => ['field' => 'status', 'operator' => '='],
        'tid' => ['field' => 'tid', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'gm_name', 'title' => '所属项目'],
            ['dataIndex' => 'status_text', 'title' => '回传状态'],
            ['dataIndex' => 'reason', 'title' => '失败原因'],
        ];
    }

    /**
     * 订单查询
     *
     * @Author hfh_wind
     * @param $request
     * @return mixed
     */
    public function search($request,$downData='')
    {
        $model = new TradeStockReturnLog();
        $model = filterModel($model, $this->filterables, $request);
        if($downData){
            //下载提供数据
            $lists=$model->orderBy('created_at', 'desc')->orderBy('id', 'desc')->get();
        }else{
            $lists = $model->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($request['per_page']??10);
        }

        return $lists;
    }
}
