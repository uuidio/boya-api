<?php

/**
 * DownloadBase.php
 * @Author: nlx
 * @Date:   2020-07-06 11:00:27
 * @Last Modified by:   nlx
 */
namespace ShopEM\Services\DownloadAct;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OSS\Core\OssException;
use OSS\OssClient;
use ShopEM\Exports\DownLoadMap;
use ShopEM\Models\DownloadLog;

class DownloadBase
{
    protected $method;

    public function __construct($method)
    {
        $this->method = $method;
    }


    //导出
    public function exportJob($data=[])
    {
        $method = $this->method;
        $objService = '\\ShopEM\\Services\\DownloadAct\\'.$method;
        try {

            $service = new $objService;
            $log_id = $data['log_id'];
            
            $exportData = $service->downloadJob($data);
            $filePath = $service->setSuffix('xlsx')->getFilePath();

            //更新下载文件日志状态及文件下载地址-队列导出
            $this->setUrl($exportData, $filePath, $log_id);
            //开启测试模式，直接打印到日志中
            // workLog(['filePath'=>$filePath,'data'=>$exportData],'export-download',$method);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            workLog($message,'export-download',$method);
            throw new \Exception($message);
        }
        workLog($filePath.':succ','export-download',$method);
        return true;
    }

    /**
     * 生成下载链接
     *
     * @Author djw
     * @param $exportData
     * @param $filePath
     * @param $log_id
     * @return string
     * @throws \OSS\Core\OssException
     */
    public function setUrl($exportData, $filePath, $log_id = false)
    {
        try
        {
            if (!is_array($exportData)) {
                DownloadLog::where('id', $log_id)->update(['url' => $url, 'status' => 2]);
                return '';
            }
            $res = Excel::store(new DownLoadMap($exportData), $filePath);
            $url = config('filesystems.disks.oss.domain') . $filePath;
            if (isset($url) && !empty($url))
            {
                DownloadLog::where('id', $log_id)->update(['url' => $url, 'status' => 1]);

            }
            else
            {
                DownloadLog::where('id', $log_id)->update(['url' => $url, 'status' => 2]);
            }
        }
         catch (\Exception $e)
        {
            $message = $e->getMessage();
            throw new \Exception($message);
        }

        return $url ?? '';
    }

}