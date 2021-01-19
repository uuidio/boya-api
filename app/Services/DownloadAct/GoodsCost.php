<?php

/**
 * GoodsCost
 * @Author: nlx
 * @Date:   2020-07-06 17:08:19
 * @Last Modified by:   nlx
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;

class GoodsCost extends Common
{
	
	protected $tableName = '成本价订单';

	public function getFilePath()
	{
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
        
		return $path;
	}

	public function downloadJob($data)
	{
		#处理导出的数据
		$id = $data['log_id'];
        $info = DB::table('download_logs')->where('id' , $id)->select('desc','gm_id','shop_id')->first();

        if(isset($info->desc) && !empty($info->desc))
        {
            $log_info = json_decode($info->desc);
            $log_info = (array)$log_info;
        }
        else
        {
            $log_info = [];
        }

        $repository = new \ShopEM\Repositories\TradePolymorphicRepository;

        $lists = $repository->search($log_info, 1);

        if (empty($lists))
        {
            return [];
        }
        $title = $repository->GoodsCostListFields();

        $export_title = array_column($title,'title'); //表头
        $filterVal = array_column($title,'dataIndex'); //表头字段

        $exportData = []; //声明导出数据
        try
        {
            // 提取导出数据
            foreach ($lists as $k => $v)
            {
                foreach ($filterVal as $fv)
                {
                    $exportData[$k][$fv] = $v[$fv] ? $v[$fv] : '';
                }
            }
            array_unshift($exportData, $export_title); // 表头数据合并

        }
        catch (\Exception $e)
        {
            $message = $e->getMessage();
            throw new \Exception($message);
        }
        return $exportData;
	}
}