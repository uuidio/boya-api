<?php
/**
 * @Filename        DownloadService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */
namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OSS\Core\OssException;
use OSS\OssClient;
use ShopEM\Exports\DownLoadMap;
use ShopEM\Models\DownloadLog;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserCowCoinLog;
use ShopEM\Repositories\TradeRepository;
use ShopEM\Repositories\UserAccountRepository;
use ShopEM\Services\DownloadAct\DownloadBase As UseService;


class DownloadService
{

    public function __construct()
    {
        // parent::__construct($gmId=1);
    }

    /**
     * 中转
     * @Author Huiho
     */
    public function Acting($data)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);
        $res = DownloadLog::where('id', $data['log_id'])->first();
        
        try {
            $service = new UseService($res->type);
            $service->exportJob($data);
        } catch (\Exception $e) {
            return false;
            // throw new \Exception($e->getMessage());
        }
        return true;
    }


}
