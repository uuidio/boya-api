<?php
/**
 * @Filename YitianGroupErpServices.php
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-04-15 09:46:01
 * @version 	V1.0
 */
namespace ShopEM\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use ShopEM\Models\Payment;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradePaybill;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\UserPointLog;
use ShopEM\Jobs\UpdateCrmUserInfo;

class YitianGroupErpServices {

    protected $client;
    protected $client_id;
    protected $client_secret;
    protected $ent_id;
    protected $gmId;
    protected $AppId;
    protected $Secret;
    protected $appCode = '';
    protected $corpCode = '';
    protected $orgCode = '';
    protected $cardTypeCode;

    protected $ServiceSataus = true;
    protected $expiresAt;
    public function __construct()
    {
        $this->client = new client(['base_uri' => env('YITIAN_ERP_BASE_URI')]);
        // $this->gmId = $gmId;
        // $platform = $this->getPlatform($gmId);
        // $this->serviceDeploy($gmId,$platform);
        // //过期时间
        // $this->expiresAt = \Carbon\Carbon::now()->addMinutes(5);
    }
    /**
     * [serviceDeploy 全局服务配置]
     * @param  [type] $gmId     [description]
     * @param  [type] $platform [description]
     * @return [type]           [description]
     */
    public function serviceDeploy($gmId,$platform)
    {
        if(empty($platform) && $gmId != 1)
        {
            $this->ServiceSataus = false;
            return true;
        }
        if (empty($platform) && $gmId == 1)
        {
            //正式环境
            $this->client = new client(['base_uri' => env('YITIAN_BASE_URI')]);
            $this->AppId = env('YITIAN_APP_ID');
            $this->Secret = env('YITIAN_SECRET');
            $this->appCode = env('YITIAN_APP_CODE');
            $this->corpCode = env('YITIAN_CORP_CODE');
            $this->orgCode = env('YITIAN_ORG_CODE');
            $this->cardTypeCode = '8001';
        }
        if ($platform)
        {
            $this->client = new client(['base_uri' => $platform->base_uri]);
            $this->AppId = $platform->app_id ;
            $this->Secret = $platform->secret ;
            $this->appCode = $platform->app_code ;
            $this->corpCode = $platform->corp_code ;
            $this->orgCode = $platform->org_code ;
            $this->cardTypeCode = $platform->default_type_code ;
        }
    }


    public function getPlatform($gmId)
    {
        $hour = \Carbon\Carbon::now()->addHour();
        $gm_cache_key = 'yitan_gm_platform_id_'.$gmId;
        // $gmData = GmPlatform::find($gmId);
        $gmData = Cache::remember($gm_cache_key, $hour, function () use ($gmId){
            return GmPlatform::find($gmId);
        });
        $error_text = '';
        if (empty($gmData)) $error_text = '该项目ID-'.$gmId.'还没创建';
        if ($gmData && empty($gmData->base_uri)) $error_text = '该项目:'.$gmData->platform_name.'还未进行配置';
        if ($gmData && empty($gmData->base_uri)) $error_text = '该项目:'.$gmData->default_type_code.'还未进行会员等级配置';

        if (!empty($error_text))
        {
            Cache::forget($gm_cache_key);
            $this->errorLog($error_text);
            return [];
        }
        return $gmData;
    }

    /**
     * 获取token
     *
     * @Author djw
     * @return mixed
     * pass
     */
    public function getToken() {
        $seconds = \Carbon\Carbon::now()->addMinute(100);
        $cache_key = 'yapi_token_'.$this->gmId;
        $token = Cache::remember($cache_key, $seconds, function (){
            $api = 'platform/token';
            $api .= '?appid='.$this->AppId;
            $api .= '&secret='.$this->Secret;
            $respond = $this->client->request('GET', $api);
            if ($respond->getStatusCode() === 200) {
                $result = $respond->getBody()->getContents();
                if (!is_null(json_decode($result))) {
                    $result = json_decode($result, true);
                    if (isset($result['Result']) && $result['Result']['HasError'] == true) {
                        throw new \Exception('CRM:token获取失败,'.$result['Result']['ErrorMessage']);
                    }
                    return $result['token'];
                }
                return $result;
            }
            return false;
        });

        if (!$token) {
            Cache::forget($cache_key);
            $this->errorLog('无法获取token');
        }
        return $token;
    }

    // 接口上传销售至 ERP
    public function upLoadTransData($params)
    {
        $pay_time = strtotime($params['pay_time']);
        $api = 'pos/UpLoadTransData';
        $order = TradeOrder::where('oid',$params['oid'])->first();
        $bill = TradePaybill::where('tid',$order->tid)->first();
        $payment = Payment::where('payment_id',$bill->payment_id)->first();
        $body['data'] = [
            'storeCode'     => $params['erp_storeCode'],
            'posCode'       => $params['erp_posCode'],
            'reciptNo'      => $params['oid'],
            'transID'       => getUuid(1),
            'productName'   => $params['goods_name'],
            'price'         => $params['amount'],
            'transDate'     => date('Y-m-d',$pay_time).'T'.date('H:i:s',$pay_time),
            'DataSource'    => 9,
            'transDetailList' => [
                'ProductCode'   =>  $order->sku_id,
                'Price'         =>  $order->goods_price,
                'Num'           =>  $order->quantity,
            ],
            'transPayList'  =>  [
                'PayCode'   =>  $payment->pay_app,
                'PayAmt'    =>  $order->amount,
            ],
        ];
        $data = [
            'json' => $body
        ];

        $result = $this->request($api, $data);
        testLog(['erp'=>$result]);
        if (isset($result['Result']) && $result['Result']['HasError'] == true) {
            $error = $result['Result']['ErrorMessage'];
            // $this->errorLog($error);
            throw new \Exception($error);
            // return false;
        }
        return $result['Data'];
    }

    /**
     * 错误日志记录
     *
     * @Author moocde <mo@mocode.cn>
     * @param $info
     */
    public function errorLog($info)
    {
        $filename = storage_path('logs/' . 'yapi-erp-errorlog-' . date('Y-m-d') . '.log');
        file_put_contents($filename, '[' . date('Y-m-d H:i:s') . '] ' . print_r($info, true) . "\n", FILE_APPEND);
    }

    /**
     * 请求接口
     *
     * @Author djw
     * @param $api
     * @param $data
     * @return bool|mixed
     */
    public function request($api, $data)
    {
        if (!$this->ServiceSataus) {
            return false;
        }
        $cache_key = 'yapi_token_'.$this->gmId;
        try {
            // $api_url = $this->setApiToken($api);
            $api_url = $api;

            //设置请求头
            if (!isset($data['headers'])) {
                $data['headers'] = [
                    'Content-Type' => 'application/json'
                ];
            }
            //设置shared
            if (!isset($data['json']['shared'])) {
                $data['json']['shared'] = $this->getShared();
            }
            // dd($data);
            testLog(['api_url'=>$api_url,'data'=>$data]);
            $result = false;
            $respond = $this->client->request('POST', $api_url, $data);

            if ($respond->getStatusCode() === 200) {
                $result = json_decode($respond->getBody()->getContents(), true);
                //如果令牌过期，刷新令牌并重新请求
                if (isset($result['Result']) && $result['Result']['ErrorCode'] == 401 && $result['Result']['ErrorMessage'] == 'Invalid Token or expired.') {
                    Cache::forget($cache_key);
                    $result = $this->request($api, $data);
                }
            }
        }
        catch(\Exception $e)
        {
            return false;
        }
        return $result;
    }

    /**
     * 获取shared
     *
     * @Author djw
     * @return mixed
     */
    private function getShared() {
        return [
            'appCode' => $this->appCode,
            'corpCode' => $this->corpCode,
            'orgCode' => $this->orgCode,
        ];
    }

    /**
     * 为api加上token
     *
     * @Author djw
     * @param $old_url
     */
    private function setApiToken($api){
        //检查链接中是否存在 ?
        $check = strpos($api, '?');
        //如果存在 ?
        if($check !== false)
        {
            if(substr($api, $check+1) != '')
            {
                //如果有参数
                $api .= '&';
            }
        }
        else //如果不存在 ?
        {
            $api .= '?';
        }
        $token = $this->getToken();
        return $api . 'token=' . $token;
    }

}
