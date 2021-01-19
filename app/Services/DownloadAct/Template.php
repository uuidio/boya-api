<?php

/**
 * @Date:   2020-07-06 15:38:30
 * @Last Modified by:   nlx
 */
namespace ShopEM\Services\DownloadAct;
use Illuminate\Support\Facades\DB;

//替换成方法名
class Template extends Common
{
	//替换成  导出的文件名
	protected $tableName = '导出数据'; //模板XXX

	public function getFilePath()
	{
        $path = $this->tableName ."_" . date('Y-m-d_H_i_s') . '.'. $this->suffix;
		return $path;
	}

	public function downloadJob($data)
	{
		#处理导出的数据
	}
}