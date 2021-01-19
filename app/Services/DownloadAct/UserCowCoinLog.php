<?php

/**
 * Template.php
 * @Date:   2020-07-06 15:38:30
 * @Last Modified by:   nlx
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\UserAccount;

class UserCowCoinLog extends Common
{

    protected $tableName = '积分转牛币列表';

    public function getFilePath()
    {
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
        
        return $path;
    }

    public function downloadJob($data)
    {
        $info = DB::table('download_logs')->where('id' ,$data['log_id'])->select('desc','gm_id','shop_id')->first();

        if(isset($info->desc) && !empty($info->desc))
        {
            $log_info = json_decode($info->desc);
            $log_info = (array)$log_info;
        }
        else
        {
            throw new \Exception('参数有误');
        }

        $select = ['before_gm_id','user_id','before_point','before_cowcoin','point','cowcoin','created_at','after_point','after_cowcoin','parities'];
        $model = DB::table('user_cow_coin_logs');


        if (isset($log_info['from']) && !empty($log_info['from']))
        {
            $model = $model->where('created_at','>=' , $log_info['from']);
        }
        if (isset($log_info['to']) && !empty($log_info['to']))
        {
            $model = $model->where('created_at','<=' , $log_info['to']);
        }
        if (isset($log_info['gm_id']) && $log_info['gm_id']>0)
        {
            $model = $model->where('after_gm_id',$log_info['gm_id']);
        }

        $lists = $model->select($select)->orderBy('created_at','asc')->get();
        $lists = $lists->toArray();
        $gm_ids = array_column($lists,'before_gm_id');
        $gm_platform=GmPlatform::whereIn('gm_id',$gm_ids)->select('gm_id','platform_name')->get()->toArray();
        $gm_arr=[];
        foreach($gm_platform as $key =>$item){
            $gm_arr[$item['gm_id']]=$item['platform_name'];
        }
        $user_ids = array_column($lists,'user_id');
        $user=UserAccount::whereIn('id',$user_ids)->select('id','mobile')->get()->toArray();
        $user_arr=[];
        foreach($user as $key =>$item){
            $user_arr[$item['id']]=$item['mobile'];
        }
        if (empty($lists))
        {
            return [];
        }else{
            foreach($lists as $key => &$value){
                if(is_object($value)) $value = (array)$value;
                $lists[$key]['before_gm_name'] = $gm_arr[$value['before_gm_id']];
                $lists[$key]['mobile'] = $user_arr[$value['user_id']];
            }
        }

        $filterVal = ['before_gm_name','mobile','before_point','before_cowcoin','point','cowcoin','created_at','after_point','after_cowcoin','parities'];

        $exportData = []; //声明导出数据

        try {


            //获取下载表头
            $export_title = ['项目名称','手机号','兑换前积分数','兑换前牛币数','使用积分','兑成牛币','兑换时间','兑换后积分数','兑换后牛币数','牛币兑换比例']; //表头
            // 提取导出数据
            foreach ($lists as $k => $v)
            {
                if(is_object($v)) $v = (array)$v;

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