<?php
/**
 * @Filename        LiveService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */
namespace ShopEM\Services\Live;
// 导入对应产品模块的client
use TencentCloud\Live\V20180801\LiveClient;
use TencentCloud\Vod\V20180717\VodClient;
use TencentCloud\Live\V20180801\Models\CreateLiveRecordRequest;
use TencentCloud\Live\V20180801\Models\StopLiveRecordRequest;
use TencentCloud\Vod\V20180717\Models\SearchMediaRequest;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Common\Credential;
use TencentCloud\Live\V20180801\Models\CreateLiveRecordResponse;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use Illuminate\Support\Facades\Redis;
use ShopEM\Models\LivesLog;
use ShopEM\Models\Lives;
use ShopEM\Services\Live\ImService;
use ShopEM\Models\UserShopFavorite;
use ShopEM\Services\SubscribeMessageService;
use ShopEM\Models\WxMessagsSubscribes;
use Carbon\Carbon;
use ShopEM\Models\WxUserinfo;
use Illuminate\Support\Facades\Log;
use ShopEM\Models\LiveRebroadcast;

class LiveService
{
    public function goodsSave($data)
    {
        $resp = json_encode([
            'type' => 'goods',
            'live_id' => $data['live_id'],
            'goods_id' => $data['goods_id']
        ]);
        return $resp;
    }


    public function createLiveRecord($StreamName)
    {

        // 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
        $cred = new Credential("AKIDNCtPipKxz87tZkncJpCNPDgxKjdZ33ke", "tb6XgdOI1ZvFTVM1TqBQvEMIzVzHUYQg");

        // # 实例化要请求产品(以cvm为例)的client对象

        // 实例化一个http选项，可选的，没有特殊需求可以跳过
        $httpProfile = new HttpProfile();
        $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
        $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
        $httpProfile->setEndpoint("live.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)

        // 实例化一个client选项，可选的，没有特殊需求可以跳过
        $clientProfile = new ClientProfile();
        $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
        $clientProfile->setHttpProfile($httpProfile);

        $client = new LiveClient($cred, "ap-shanghai", $clientProfile);
        $req = new CreateLiveRecordRequest();
        #$req->Action = "stress_test_956938";
        $req->StreamName = $StreamName;
        $req->Highlight = "1";
        $req->FileFormat = 'hls';
        # $req->AppName = "a";

        $resp = $client->CreateLiveRecord($req);

        // 输出json格式的字符串回包
        $re =  json_decode($resp->toJsonString(),1);

        return $re;
    }

    public function stopLiveRecord($data)
    {
        // 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
        $cred = new Credential("AKIDNCtPipKxz87tZkncJpCNPDgxKjdZ33ke", "tb6XgdOI1ZvFTVM1TqBQvEMIzVzHUYQg");

        // 实例化一个http选项，可选的，没有特殊需求可以跳过
        $httpProfile = new HttpProfile();
        $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
        $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
        $httpProfile->setEndpoint("live.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)

        // 实例化一个client选项，可选的，没有特殊需求可以跳过
        $clientProfile = new ClientProfile();
        $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
        $clientProfile->setHttpProfile($httpProfile);

        // # 实例化要请求产品(以cvm为例)的client对象
        $client = new LiveClient($cred, "ap-shanghai", $clientProfile);
        $req = new StopLiveRecordRequest();
        #$req->Action = "stress_test_956938";
        $req->StreamName = $data['streamname'];#"1586763960";
        $req->TaskId = $data['taskid'];

        $resp = $client->StopLiveRecord($req);

        // 输出json格式的字符串回包
        $re =  json_decode($resp->toJsonString(),1);

        return true;

    }

    public function searchVod($data)
    {
        // 实例化一个证书对象，入参需要传入腾讯云账户secretId，secretKey
        $cred = new Credential("AKIDNCtPipKxz87tZkncJpCNPDgxKjdZ33ke", "tb6XgdOI1ZvFTVM1TqBQvEMIzVzHUYQg");

        // 实例化一个http选项，可选的，没有特殊需求可以跳过
        $httpProfile = new HttpProfile();
        $httpProfile->setReqMethod("GET");  // post请求(默认为post请求)
        $httpProfile->setReqTimeout(30);    // 请求超时时间，单位为秒(默认60秒)
        $httpProfile->setEndpoint("vod.tencentcloudapi.com");  // 指定接入地域域名(默认就近接入)

        // 实例化一个client选项，可选的，没有特殊需求可以跳过
        $clientProfile = new ClientProfile();
        $clientProfile->setSignMethod("TC3-HMAC-SHA256");  // 指定签名算法(默认为HmacSHA256)
        $clientProfile->setHttpProfile($httpProfile);

        $client = new VodClient($cred, "ap-shanghai", $clientProfile);
        $req = new SearchMediaRequest();
        $req->SourceType = 'Record';
        $req->StreamId = $data['streamname'];
        $req->StartTime = $data['start_time'];
        #  $req->EndTime = $data['end_time'];

        $resp = $client->SearchMedia($req);

        $re =  json_decode($resp->toJsonString(),1);
        if(empty($re['MediaInfoSet'])){
            $vod = null;
        }else{
            $vod = $re['MediaInfoSet'][0]['BasicInfo']['MediaUrl'];
        }
        return $vod;
    }

    public function swooleSend($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://test.jumhz.com/ws-http');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);

        return true;
    }

    public function streamEnd($parameter)
    {
        $liveId = $parameter['id'];
        $shopId = $parameter['shop_id'];
        $sequence = $parameter['sequence'];
        try {
            $Lives = Lives::find($liveId);
            if (empty($Lives)) {
                throw new \LogicException('找不到数据');
            }
            if($Lives['live_status'] == '0') {
                throw new \LogicException('直播已关闭');
            }
            $log = LivesLog::where('live_id', '=', $liveId)->where('sequence', '=', $sequence)->select('id', 'type', 'start_at', 'playback', 'task_id','sequence','live_status')->orderBy('id', 'desc')->first();
            if (empty($log)) {
                throw new \LogicException('找不到数据');
            }
            if($log['live_status'] != 1) {
                throw new \LogicException('找不到数据');
            }
            $service = new ImService();
            $historyFavoriteCount = UserShopFavorite::where('shop_id', '=', $shopId)->count();
            $chcheFavoriteCount = Redis::get('collect' . $liveId);
            $favoriteCount = $historyFavoriteCount - $chcheFavoriteCount;
            if ($favoriteCount > 0) {
                $favoriteCount = $favoriteCount;
            } else {
                $favoriteCount = '0';
            }
            $likeCount = Redis::get('like' . $liveId);
            $hotCount = Redis::get('hot' . $liveId);
            $audienceJson = Redis::get('audience' . $liveId);
            if ($audienceJson) {
                $audienceCount = count(json_decode($audienceJson, 1));
            } else {
                $audienceCount = '0';
            }
            $chatroomAddr = $service->chatroomToggle(['roomid' => $Lives['roomid'], 'operator' => $Lives['accid'], 'valid' => 'false']);
            LivesLog::where('id', '=', $log['id'])->update(['end_at' => date("Y-m-d H:i:s"), 'like' => $likeCount, 'heat' => $hotCount, 'audience' => $audienceCount, 'collect' => $favoriteCount,'live_status'=>'0']);
            $data['id'] = $liveId;
            $data['live_status'] = 0;
            $data['im_valid'] = 0;
            $data['showid'] = null;

            Lives::where('rebroadcasts_id',$liveId)->update(['rebroadcasts_id'=>0]);
            Lives::where('id',$liveId)->update(['rebroadcast'=>0]);
            LiveRebroadcast::where('rebroadcasts_live',$liveId)->update(['rebroadcasts'=>0,'rebroadcasts_status'=>3]);
            //删除缓存
            Redis::del('like' . $liveId);
            Redis::del('hot' . $liveId);
            Redis::del('swoole' . $liveId);
            Redis::del('audience' . $liveId);
            Redis::del('collect' . $liveId);
            $Lives->update($data);
        } catch (\Exception $e) {
            $code['code'] = 0;
            $recode = json_encode($code);exit;
            throw new \Exception($e->getMessage());
        }
        return true;
    }

    public function subscribeSend()
    {
        $list = WxMessagsSubscribes::where('send_at' ,'<',  Carbon::now()->toDateTimeString())->where('type','=','0')->orderBy('send_at', 'asc')->get();
        $msgService = new SubscribeMessageService();
        if($list){
            foreach ($list as $key => $value)
            {
                $user_info = WxUserinfo::where('user_id', $value['user_id'])->where('user_type','1')->select('openid')->first();
                $touser = $user_info['openid'];
                $send = json_decode($value['data'],1);
                $get_send_msg = $msgService->SendSubscribeMessage($touser,'n5tnA5FRAi6p5RbcJZ6levDbekApsWy-e9yWiK-rRTo',$value['page'],$send);
                if ($get_send_msg['errcode'] == 0) {
                    WxMessagsSubscribes::where('id','=',$value['id'])->update(['type'=>'1']);
                }else{
                    WxMessagsSubscribes::where('id','=',$value['id'])->update(['type'=>'1']);
                    Log::info('subscribeSend'.json_encode($get_send_msg));
                }
            }
        }

        return true;
    }
}
