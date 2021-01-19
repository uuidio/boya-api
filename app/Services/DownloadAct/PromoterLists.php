<?php

/**
 * PromoterLists.php
 * @Author: nlx
 * @Date:   2020-07-06 15:38:30
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-07-07 10:00:26
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;

class PromoterLists extends Common
{
	
	protected $tableName = '佣金管理';

	public function getFilePath()
	{
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
        
		return $path;
	}

	public function downloadJob($data)
	{
		#处理导出的数据
		$repository = new \ShopEM\Repositories\ApplyPromoterRepository();
        $lists = $repository->search($data ,'down');

        if (empty($lists)) {
            return [];
        }

        $exportData = []; //声明导出数据

        try {

            $lists = $lists->toArray();

            //获取下载表头
            $title = $repository->PromoterListsShowFields(1);//需要导出的字段

            $export_title = array_column($title, 'title'); //表头

            // 提取导出数据
            foreach ($lists as $k => $v) {
                foreach ($title as $fv) {
                    if ($fv['key'] == 'nickname') {
                        $nickname = $this->filterEmoji(urldecode($v[$fv['key']]));
                        $v[$fv['key']]=$this->startWith($nickname, "=") ? str_replace("=", "-", $nickname) : $nickname;
                    }
                    if (isset($fv['set_key'])) {

                        $exportData[$k][$fv['set_key']] = $v[$fv['key']][$fv['value']]??'';

                    } else {
                        $exportData[$k][$fv['key']] = $v[$fv['key']] ? $v[$fv['key']] : '';
                    }
                }

            }
            array_unshift($exportData, $export_title); // 表头数据合并

        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Exception($message);
        }

        return $exportData;
	}
}