<?php
/**
 * @Filename        TestController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Imports\ImportTest;
use ShopEM\Models\Brand;
use ShopEM\Models\GoodsClass;
use ShopEM\Models\ShopFloor;
use ShopEM\Repositories\ShopRelCatsRepository;
use ShopEM\Repositories\ShopRepository;
use ShopEM\Jobs\SplitTrade;
use ShopEM\Jobs\CloseSecKillTrade;
use ShopEM\Models\Shop;
use ShopEM\Models\Payment;
use ShopEM\Models\Trade;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\TradePaybill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ShopEM\Exports\DownLoadMap;
use Maatwebsite\Excel\Facades\Excel;
use ShopEM\Services\BusinessCloud\PayService;
use ShopEM\Services\PaymentService;
use ShopEM\Services\Upload\UploadImage;
use ShopEM\Services\Xinpoll\Sdk;



class TestController extends BaseController
{
    public function test1(Request $Request)
    {
        $params['log_id'] = $Request->id;
        $service =  new  \ShopEM\Services\DownloadService();
        $cc = $service->Acting($params);
        // $service = new  \ShopEM\Services\YitianGroupServices(1);
        // // $model = \ShopEM\Models\TradeOrder::where('oid','50191016100652657737')->first();
        // // $use_obtain_point = $gm->use_obtain_point;
        // // $scale = $use_obtain_point['obtain_point']/$use_obtain_point['use_point'];
        // // $points = 10;
        // // $model['erp_storeCode'] = '1199';
        // // $model['erp_posCode'] = '180112';
        // $cc = $service->masterStoreList();
        // $new = new \ShopEM\Services\Yitian\StoreService();
        // $dd = $new->saveList($cc,1);

        // $cc = $service->memberInfo(18296237954);
        var_dump($cc);exit;

    }


    public function testDown(Request $request)
    {
        $objService = '\\ShopEM\\Services\\DownloadAct\\'.$request->method;
        try {

            $service = new $objService;
            $data = $request->all();

            $exportData = $service->downloadJob($data);
            $filePath = $service->setSuffix('xlsx')->getFilePath();
            return Excel::download(new DownLoadMap($exportData), $filePath);

            //更新下载文件日志状态及文件下载地址-队列导出
            // $this->setUrl($exportData, $filePath, $log_id);
            //开启测试模式，直接打印到日志中
            // workLog(['filePath'=>$filePath,'data'=>$exportData],'export-download',$method);

        } catch (\Exception $e) {
            $message = $e->getMessage();
            throw new \Exception($message);
        }
    }


    /**
     * 导入兑换码
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ImportTest(Request $request)
    {

        $uploadImage = new UploadImage($request);

        $filePath = $uploadImage->uploadFile_document('xlsx');

        if (!empty($filePath)) {

            Excel::import(new ImportTest, $filePath);

            return $this->resSuccess([], "导入成功!");
        }

        return $this->resFailed(603, "上传错误");
    }

    public function index()
    {
        $service = new   \ShopEM\Services\GoodsImportService();

        $service->act_insert();

      /*    $sql = "SELECT  *  from  em_goods_import_details  group by gc_1";

       $db = DB::select($sql);

       foreach ($db  as  $value){

           $data['gc_name'] = $value->gc_1;
           $data['parent_id'] = 0;
           $data['class_level'] = 1;
           $data['gm_id'] = 2;
//           dd($data);
           GoodsClass::create($data);
       }*/

      /*  $db=GoodsClass::where(['parent_id'=>0])->get();

        foreach ($db  as  $value){

            $sql = "SELECT  *   from  em_goods_import_details  where gc_1='".$value->gc_name."'   group by gc_2";

            $son = DB::select($sql);
            foreach ($son  as  $v) {
                $data['gc_name'] = $v->gc_2;
                $data['parent_id'] = $value['id'];
                $data['class_level'] = 2;
                $data['gm_id'] = 2;

                $check=GoodsClass::where(['class_level'=>2,'gc_name'=>$v->gc_2])->count();
                if(!$check){
                    GoodsClass::create($data);
                }
            }

        }*/

      /*  $db=GoodsClass::where(['class_level'=>2])->get();

        foreach ($db  as  $value){

            $sql = "SELECT  *   from  em_goods_import_details  where gc_2='".$value->gc_name."'   group by gc_3";

            $son = DB::select($sql);

            foreach ($son  as  $v) {
                $data['gc_name'] = $v->gc_3;
                $data['parent_id'] = $value['id'];
                $data['class_level'] = 3;
                $data['gm_id'] = 2;

                $check=GoodsClass::where(['class_level'=>3,'gc_name'=>$v->gc_3])->count();
                if(!$check){
                    GoodsClass::create($data);
                }
            }

        }*/

dd(111);

        try {

        } catch (Exception $e) {
            throw $e;
        }

        $statService = new   \ShopEM\Services\TradeSettleService();
        $day = "-1";
        $params = array(
            'time_start'  => date('Y-m-d 00:00:00', strtotime($day . ' day')),
            'time_end'    => date('Y-m-d 23:59:59', strtotime($day . ' day')),
            'time_insert' => date('Y-m-d H:i:s', strtotime($day . ' day')),
        );
       // $params = array(
       //     'time_start'  => date('Y-m-d 00:00:00'),
       //     'time_end'    => date('Y-m-d 23:59:59'),
       //     'time_insert' => date('Y-m-d H:i:s'),
       // );

        //日结订单数据表
        // $statService->tradeDayDetail($params);
        //日结数据表
        // $statService->tradeDay($params);exit;

       // $test= new \ShopEM\Services\TradeSpitService();
       // $param['payment_id']='10191023173204641078';
       // $param['user_id']='1';

       // $test->setPayment($param);
        //$statService->_repairData();exit;

    }


    public function index2()
    {
        // $payment_id='100200513141920938196';
        // //拆单
        // SplitTrade::dispatch($payment_id);

    }

    // public function index3()
    // {

        // $statService = new   \ShopEM\Services\UVStatisticsService();
        // $day = "-1";
        // $params = array(
        //     'time_start'  => date('Y-m-d 00:00:00', strtotime($day . ' day')),
        //     'time_end'    => date('Y-m-d 23:59:59', strtotime($day . ' day')),
        //     'time_insert' => date('Y-m-d', strtotime($day . ' day')),
        // );

        // $statService->YesterdayData($params);exit;

    // }


    public function index3(Request $request)
    {
        CloseSecKillTrade::dispatch($request->tid);
    }


    public function updateSql(Request $request)
    {
        // $value = '434200603152515457378';
        // $str1 = mb_substr($value,0,1,'utf-8');
        // echo substr_replace($value,"********",6,8);
        // return $this->downloadTest();
        if ($request->passwork != 'lanlnk@update@2020') {
            echo '406 非法操作';
            exit;
        }
        DB::beginTransaction();
        try {
            $m = $request->method;
            $this->$m();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());

        }
        echo "succ1";
    }

    public function TradeCancel()
    {
       $data =  DB::table('trades')->where('activity_sign','seckill')->where('status','WAIT_BUYER_PAY')->get();
       $tradeService = new \ShopEM\Services\TradeService();

       foreach ($data as $key => $value)
       {
            $value = (array)$value;
            $tradeService->PlatformQueueTradeCancel($value['tid'], '系统取消');
           // CloseSecKillTrade::dispatch($value['tid']);
       }
    }


    public function TradeSucc()
    {
        $tradeParams['status'] = 'WAIT_BUYER_PAY';
        $tradeParams['cancel_status'] = 'NO_APPLY_CANCEL';
        $tradeParams['cancel_reason'] = null;

        $orderParams['status'] = 'WAIT_BUYER_PAY';
        return '支付成功但是被关闭订单恢复代码';
        DB::beginTransaction();
        try {
            $data = [
                ['tid' => '300200820184335053126' , 'transaction_id' => '4200000721202008209181137734'],
            ];
            foreach ($data as $key => $value) {
                $filter['tid'] = $value['tid'];
                DB::table('trades')->where($filter)->update($tradeParams);
                DB::table('trade_orders')->where($filter)->update($orderParams);

                $payment_data = [];
                $payment_id = DB::table('trade_paybills')->where($filter)->value('payment_id');
                $payment_info = DB::table('payments')->where('payment_id', $payment_id)->first();

                // $transaction_id = $value['transaction_id'];
                $payment_data['trade_no'] = $value['transaction_id'];
                $payment_data['payment_id'] = $payment_id;
                $payment_data['pay_app'] = $payment_info->pay_app;
                $payment_data['user_id'] = $payment_info->user_id;


                PaymentService::paySuccess($payment_data, '');

                \ShopEM\Models\TradeCancel::where($filter)->delete();
            }
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());

        }
        return true;


    }

    public function update1()
    {
        $data = [
            'mobile'    => '13126078438',
            'gm_name'   => '牛小子',
            'change'    => '-20',
            'point'     => '100',
            'time'      => date('Y-m-d'),
            'reason'    => '$reason',
        ];
        (new \ShopEM\Models\WechatMessage())->pointChangeMessage($data);
        $goods_ids = DB::table('point_activity_goods')->get();
        // DB::table('goods')->whereIn('id',$goods_ids)->update(['is_point_activity'=>1]);
        foreach ($goods_ids as $key => $value)
        {
            DB::table('goods')->where('id',$value->goods_id)->update(['is_point_activity'=>1]);
            // $gm_id = \ShopEM\Models\Goods::where('id',$value['goods_id'])->value('gm_id');
            // DB::table('goods_stock_logs')->where('goods_id',$value['goods_id'])->whereNotIn('gm_id',[$gm_id])->update(['gm_id'=>$gm_id]);
        }

    }
    public function createToken()
    {
        $user = \ShopEM\Models\UserAccount::find(2);
        $token = $user->createToken('api')->accessToken;
        var_dump(['Authorization'=> "Bearer " . $token]);
    }
    public function createAccountTest()
    {
        $obj = new Sdk();
        $res = $obj->issueWechat([
            'acctName'  =>  '陈典玮',
            'amount'  =>  0.01,
            'cardNo'  =>  '441881199003195612',
            'mobile'  =>  '13680002480',
            'orderNo'  =>  '434200603152515457378',
            'papersType'  =>  'ID_CARD',
            'pmtType'  =>  'PAYMENT',
            'remark'  =>  '测试代发',
        ], ['serial_id'=>'434200603152515457378'], [env('APP_URL').'/issue/xinpoll-notify']);
        return $this->resSuccess([[
            'acctName'  =>  '陈典玮',
            'amount'  =>  0.01,
            'cardNo'  =>  '441881199003195612',
            'mobile'  =>  '13680002480',
            'orderNo'  =>  '434200603152515457378',
            'papersType'  =>  'ID_CARD',
            'pmtType'  =>  'PAYMENT',
            'remark'  =>  '测试代发',
        ], ['serial_id'=>'434200603152515457378'], [env('APP_URL').'/issue/xinpoll-notify']]);
    }

    public function downloadTest()
    {
        $data['log_id'] = 95;
        $service =  new  \ShopEM\Services\DownloadService();
        return $service->Acting($data);
    }

    public function forgetSecKillListsCache($gm_id){
        $cache_key = 'cache_seckill_applie_gmid_'.$gm_id;
        \Illuminate\Support\Facades\Cache::forget($cache_key);
        return $this->resSuccess();
    }

    public function createPayId()
    {
        $service =  new  \ShopEM\Services\TradeService;
        echo $service::createPayId();
        echo "string";
        echo strlen($service::createPayId());
    }


    public function walletPay()
    {
        $amount = 150000;
        $cardList = [
            array('valid_balance'=>100000,'card_id'=>'8668083662000003605'),
            array('valid_balance'=>99300,'card_id'=>'8668083662000003415'),
            array('valid_balance'=>0,'card_id'=>'8668083662000003444'),
        ];
        $last_names = array_column($cardList,'valid_balance');
        array_multisort($last_names,SORT_ASC,$cardList);
        foreach ($cardList as $key => $value)
        {
            if ($value['valid_balance'] > 0)
            {
                $remainder = $amount - $value['valid_balance'];
                $card_ids[] = $value['card_id'];
                if ($remainder <= 0)
                {
                    $amounts[] = $amount;
                    break; // 当 一张足够满足时，终止循环
                }
                if ($remainder > 0) $amounts[] = $value['valid_balance'];
                $amount = $remainder;
                unset($remainder);
            }
        }

        $num = count($card_ids);
        var_dump($card_ids);
        var_dump($amounts);
    }

    public function luckDraw()
    {
        $yitiangroup_service = new \ShopEM\Services\YitianGroupServices(1);
        // 扣减积分
        // $params = [
        //     'num' => 1,  // 会员积分
        //     'user_id' => 85,  // 会员ID
        //     'tid' => '0',
        //     'remark' => '抽奖消耗积分',
        //     'behavior' => '抽奖消耗积分',
        //     'type' => 'consume',  // 消耗
        //     'log_type' => 'luckDraw',  // 添加
        // ];

        // $data = [
        //     'storeCode'        => 'B1W-ATM',                         //门店编码
        //     'transTime'        => '2020-08-18 17:13:06',
        //     'receiptNo'        => '4567897898988',
        //     'payableAmount'    => 100,
        //     'netAmount'        => 100,
        //     'discountAmount'   => 0,
        //     'getPointAmount'   => 100,
        //     'cardCode'         => '800100387648',
        // ];

        $point = $yitiangroup_service->memberInfo(13126078438);
        // $yitiangroup_service->updateCardTypeCode( 85, 13126078438);
        // $point = $yitiangroup_service->tradePushCrm($data);
        // $point = $yitiangroup_service->updateUserYitianPoint($params);
        dd($point);
    }

    public function getPayConfig()
    {
        $paymentId = request('payment_id',0);
        if (empty($paymentId)) return $this->resFailed(414,'参数错误');
        $res = (new PayService())->getPayConfig(Payment::find($paymentId));
        dd($res);
    }

    public function aftersales()
    {
        $data = ['700200618143252558949','700200618143320268903','700200618143519175176'];

        $getTrade = new \ShopEM\Services\TradeService();
        foreach ($data as $key => $aftersales_bn) 
        {
            $after_data = DB::table('trade_aftersales')->where('aftersales_bn',$aftersales_bn)->first();

            $update_data['progress'] = '0';
            $update_data['status'] = '0';
            $update_data['aftersales_type'] = 'ONLY_REFUND';
            DB::table('trade_aftersales')->where('aftersales_bn',$after_data->aftersales_bn)->update($update_data);

            $tradeInfo = $getTrade->__getTradeInfo($after_data->tid);
            $params = array(
                'oid'                => $after_data->oid,
                'tid'                => $after_data->tid,
                'user_id'            => $after_data->user_id,
                'after_sales_status' => 'WAIT_SELLER_AGREE',
                'tradesData'         => $tradeInfo,
            );

            $getTrade->afterSaleOrderStatusUpdate($params);
            $orderFilter = array('oid' => $params['oid'], 'user_id' => $params['user_id'], 'tid' => $params['tid']);
            // 更改子订单投诉状态
            DB::table('trade_orders')->where($orderFilter)->update(['complaint_status' => 'NOT_COMPLAINTS']);
        }
    }

    public function testTime()
    {
        $payment_data['trade_no'] = '4200000704202008256645676355';
        $payment_data['payment_id'] = '631010101999999200825112750856';
        $payment_data['pay_app'] = 'Wxpaymini';
        $payment_info = Payment::where('payment_id', $payment_data['payment_id'])->first();
        (new PaymentService)->systemDoRefund($payment_data,$payment_info);
    }
}
