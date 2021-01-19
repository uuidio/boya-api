<?php

/**
 * TradePayment.php
 * @Author: nlx
 * @Date:   2020-07-06 15:38:30
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-07-07 10:00:50
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;

class TradePayment extends Common
{
	protected $tableName = '支付订单列表';

	public function getFilePath()
	{
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
		return $path;
	}
		
	public function downloadJob($data)
	{
		$id = $data['log_id'];
		$info = DB::table('download_logs')->where('id' , $id)->select('desc','gm_id','shop_id')->first();

        if(isset($info->desc) && !empty($info->desc))
        {
            $log_info = json_decode($info->desc);
            $log_info = (array)$log_info;
        }
        else
        {
            throw new \Exception('参数有误');
        }

        $repository = new \ShopEM\Repositories\TradePaymentRepository();
        $lists = $repository->search($log_info, 1);

        if (empty($lists))
        {
            return [];
        }
        $title = $repository->listShowFields();

        $export_title = array_column($title,'title'); //表头
        $filterVal = array_column($title,'dataIndex'); //表头字段

        $exportData = []; //声明导出数据
        try
        {
            $lists = $lists->toArray();
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