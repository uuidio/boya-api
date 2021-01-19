<?php

/**
 * Template.php
 * @Date:   2020-07-06 15:38:30
 * @Last Modified by:   nlx
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\GoodsSku;

class Goods extends Common
{

    protected $tableName = '商品列表导出';

    public function getFilePath()
    {
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
        return $path;
    }

    public function downloadJob($data)
    {
        $info = DB::table('download_logs')->where('id' , $data['log_id'])->select('desc','gm_id','shop_id')->first();

        if(isset($info->desc) && !empty($info->desc))
        {
            $log_info = json_decode($info->desc);
            $log_info = (array)$log_info;
        }
        else
        {
            throw new \Exception('参数有误');
        }

        $repository = new \ShopEM\Repositories\GoodsRepository();
        $lists = $repository->listItems($log_info,false, 1);
        $lists = $lists->toArray();

        $goods_ids = array_column($lists,'id');

        $goods_sku = GoodsSku::whereIn('goods_id',$goods_ids)->select('goods_id','spec_sign')->get()->toArray();
        if (empty($lists))
        {
            return [];
        }else{
            $result=[];
            $good_arr=[];
            foreach($lists as &$value){
                if(is_object($value)) $value = (array)$value;
                $good_arr[$value['id']] = $value;
            }
//            array_merge
            foreach($goods_sku as $key => $item){
                $item['goods_sku'] = $item['spec_sign']? $item['spec_sign']:'';
                unset($item['spec_sign']);
                $result[] = array_merge($good_arr[$item['goods_id']],$item);

            }

        }


        $title = $repository->downLstFields();

        $export_title = array_column($title,'title'); //表头
        $filterVal = array_column($title,'dataIndex'); //表头字段

        $exportData = []; //声明导出数据
        try
        {

            // 提取导出数据
            foreach ($result as $k => $v)
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