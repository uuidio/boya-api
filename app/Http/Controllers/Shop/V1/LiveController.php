<?php
/**
 * @Filename        TestController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\Lives;
use ShopEM\Models\Goods;
use ShopEM\Services\Live\ImService;
use Illuminate\Support\Facades\Auth;
use ShopEM\Models\Shop;
use ShopEM\Models\Foreshows;
use Carbon\Carbon;
use ShopEM\Services\Live\LiveService;
use ShopEM\Services\User\UserShopFavoriteService;
use ShopEM\Models\LiveUsers;
use ShopEM\Models\LivesLog;
use Illuminate\Support\Facades\Redis;
use ShopEM\Repositories\LivesLogRepository;
use ShopEM\Models\WxUserinfo;
use ShopEM\Models\WxMessagsSubscribes;
use ShopEM\Models\LiveRaffle;
use ShopEM\Models\LiveRaffleLog;
use ShopEM\Services\Live\LiveAnchorService;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserShop;
use ShopEM\Services\Live\ForeshowService;

class LiveController extends BaseController
{


    public function list(Request $request)
    {
        $list = Lives::where(['live_status' => '1'])->select('title','id','streamname','img_url','introduce')->first();

        return $this->resSuccess([
            'lists' => $list,
        ]);

    }

    public function liveEdit(Request $request)
    {
        $id = $request->id;

        $Lives = Lives::find($id);
        if(empty($Lives)) {
            return $this->resFailed(701,"找不到数据!");
        }
        $list['favorite'] = false;
        $rebroadcasts = false;
        $LiveAnchorService = new LiveAnchorService();
        //转播处理
        if($Lives['rebroadcasts_id']) {
            $rebroadcastsLives = Lives::find($id);
            $rebroadcasts_id = $Lives['rebroadcasts_id'];
            $LivesRebroadcasts = Lives::find($rebroadcasts_id);
            if($LivesRebroadcasts['live_status'] == '1'){
                //如果转播间正在直播则跳转转播
                $rebroadcasts = true;
                $id = $Lives['rebroadcasts_id'];
                $Lives = Lives::find($id);
            }
        }

        if($Lives['live_status'] != '1') {
            //上场直播历史数据
            $foreshowService = new ForeshowService();
            $list = $foreshowService->liveHistory($id);
            $list['id'] = $Lives['id'];
            $list['live_status'] = $Lives['live_status'];
            $foreshow = Foreshows::where(['live_id' => $id])->where('start_at' ,'>',  Carbon::now()->toDateTimeString())->orderBy('start_at', 'asc')->count();
            if($foreshow >= '1') {
                $list['foreshow'] = true;
            }
            $shopId = $Lives['shop_id'];
        }else{
                $list = Lives::where(['live_status' => '1'])->where(['id' => $id])->select('title','id','streamname','img_url','goodids','number','subtitle','rollitle','introduce','roomid','accid','shop_id','showid')->first()->toArray();
            if($rebroadcasts){
                $shopId = $rebroadcastsLives['shop_id'];
                $list['id'] = $rebroadcastsLives['id'];
            }else{
                $shopId = $list['shop_id'];
            }

            if(Auth::guard('shop_users')->check()){
                if($request->accid) {
                    $LiveAnchorService->shareCoupon(['accid'=>$request->accid,'live_id'=>$id,'shop_id'=>$Lives['shop_id']]);
                }
                $accid = $this->user->accid;
                $list['accid'] = $accid;
                $list['im_token'] = $this->user->im_token;
                $data['user_id'] = $this->user->id;

                $audienceJson = Redis::get('audience'.$id);
                if($audienceJson) {
                    $audience = json_decode($audienceJson,1);
                }else{
                    $audience = NULL;
                }
                $audience[$data['user_id']] = true;
                $audienceCount = count($audience);
                $LiveService = new LiveService();
                $swoole['type'] = 'audience';
                $swoole['live_id'] = $id;
                $swoole['audienceCount'] = $audienceCount;
                $LiveService->swooleSend($swoole);
                $audienceJson = json_encode($audience);
                Redis::set('audience'.$id,$audienceJson);
                $list['pull_url'] = 'https://pull.jumhz.com/live/'.$list['streamname'].'.flv';
            }else{
                $accid = $list['accid'];
            }
            $service = new ImService();
            $im_addr = $service->getAddr(['roomid'=>$list['roomid'],'accid'=>$accid,'clienttype'=>3]);
            if($im_addr['code'] == '200') {
                $list['im_addr'] = $im_addr['addr'];
            }
            $liveLog = LivesLog::where('live_id','=',$id)->select('start_at')->orderBy('id','desc')->first();
            $list['live_status'] = $Lives['live_status'];
            $like_count = Redis::get('like'.$id);
            $hot_count = Redis::get('hot'.$id);
            $list['notice'] = $Lives['rollitle'];
            $list['like_count'] = $like_count ? $like_count : '0';
            $list['hot_count'] = $hot_count ? $hot_count : '0';
            $list['live_start_at'] = strtotime($liveLog['start_at']);
            $list['rebroadcasts'] = $rebroadcasts;
        }
        $list['shop'] = Shop::where('id','=',$shopId)->select('*')->first();
        if(Auth::guard('shop_users')->check())
        {
            UserAccount::where('id', $id)->update(['shop_id'=>$list['shop']['id']]);
            $hasShop = UserShop::where('user_id',$id)->where('shop_id',$list['shop']['id'])->first();
            if(!$hasShop) {
                UserShop::create(['user_id'=>$id,'shop_id'=>$list['shop']['id']]);
            }
            $data = $list['shop'];
            $data['user_id'] = $this->user->id;
            $favorite_data = UserShopFavoriteService::makeFavoriteInfo($data);
            //判断是否已添加
            $hasFavorite = UserShopFavoriteService::existFavorite($favorite_data['user_id'], $favorite_data['shop_id']);
            if (!empty($hasFavorite)) {
                $list['favorite'] = true;
            }
        }

        $goodids = json_decode($list['goodids'],1);
        if($goodids) {
            $goods = Goods::whereIn('id', $goodids)->where('goods_state','=','1')->get();
            $list['goods'] = $goods;
        }
        $list['anchorInfo'] = LiveUsers::where('live_id','=',$id)->select('id','username','img_url')->first();
        $list['secKillStatus'] = $LiveAnchorService->secKillCheck($shopId);

        return $this->resSuccess([
            'data' => $list,
        ]);

    }

    public function foreshow(Request $request)
    {
        $live_id = $request->live_id;
        $foreshow = Foreshows::where(['live_id' => $live_id])->where('start_at' ,'>',  Carbon::now()->toDateTimeString())->orderBy('start_at', 'asc')->first();
        if(empty($foreshow)) {
            return $this->resFailed(701,"找不到数据!");
        }
        $goods = json_decode($foreshow['goodsids'],1);
        $foreshow['start_time'] = strtotime($foreshow['start_at']);
        $foreshow['goods'] = Goods::whereIn('id',$goods)->where('goods_state','=','1')->get();
        return $this->resSuccess([
            'data' => $foreshow,
        ]);

    }

    /**
     * 回放列表
     *
     * @Author linzhe
     */
    public function playback(Request $request,LivesLogRepository $repository)
    {
        $live_id = $request->live_id;
        $data = [
           # 'per_page' => config('app.per_page'),
            'type'  =>  '2',
            'delete'=>  '0',
            'live_id' => $live_id
        ];
        $list = $repository->search($data,config('app.per_page'));
        return $this->resSuccess([
            'list' => $list,
        ]);
    }

    public function subscribe(Request $request)
    {
        $userId = $this->user->id;
        $parameter = $request->only('foreshow_id');
        $userInfo = WxUserinfo::where('user_id','=',$userId)->select('openid')->first();

        if(empty($userInfo)) {
            return $this->resFailed(701,"未授权用户!");
        }
        $Model = new Foreshows();
        $userContent = $Model->leftJoin('live_users', 'live_users.live_id', '=', 'foreshows.live_id')->where('foreshows.id', $parameter['foreshow_id'])->select('foreshows.title','foreshows.live_id','foreshows.shop_id','foreshows.start_at','live_users.username')->first();

        if(empty($userContent)) {
            return $this->resFailed(701,"找不到数据!");
        }

        if(time() > strtotime($userContent['start_at']) ) {
            return $this->resFailed(701,"已开播!");
        }

        $hasSubscribes = WxMessagsSubscribes::where('relevance_id','=',$parameter['foreshow_id'])->where('send_at' ,'>',  Carbon::now()->toDateTimeString())->where('user_id',$userId)->select('id')->first();
        if($hasSubscribes) {
            return $this->resFailed(701,"已订阅!");
        }

        $startAt = date('Y年m月d日 H:i',strtotime($userContent['start_at']));
        $sendContent = json_encode([
            'thing1' => ['value' => $userContent['username']],
            'thing2' => ['value' => $userContent['title']],
            'date7' => ['value' => $startAt],
        ]);

        $data = [
            'title' =>  $userContent['title'],
            #'template_id'   =>  $parameter['template_id'],
            'user_id'   =>  $userId,
            'data'  =>  $sendContent,
            'page'  =>  'live/pages/lives/lives?liveid='.$userContent['live_id'].'&shopid='.$userContent['shop_id'],
            'description'   =>  'live_subscribe',
            'miniprogram_state' =>  'formal',
            'send_at' =>    $userContent['start_at'],
            'relevance_id' =>    $parameter['foreshow_id'],
        ];
        WxMessagsSubscribes::create($data);
        return $this->resSuccess();
    }

    public function subscribeCheck(Request $request)
    {
        $userId = $this->user->id;
        $parameter = $request->only('foreshow_id');
        $hasForeshows = Foreshows::where('id','=',$parameter['foreshow_id'])->select('id')->first();
        if(empty($hasForeshows)){
            return $this->resFailed(701,"找不到数据!");
        }
        $hasSubscribes = WxMessagsSubscribes::where('relevance_id','=',$parameter['foreshow_id'])->where('send_at' ,'>',  Carbon::now()->toDateTimeString())->where('user_id',$userId)->select('id')->first();
        if(!$hasSubscribes) {
            $data['status'] = false;
        }else{
            $data['status'] = true;
        }
        return $this->resSuccess([
            'data' => $data
        ]);
    }

    public function raffleParticipation(Request $request)
    {
        $data = $request->only('raffles_id');
        $userId = $this->user->id;
        $raffle = LiveRaffle::where('id','=',$data['raffles_id'])->select('*')->first();
        if($raffle['status'] == '1') {
            return $this->resFailed(701,"活动已结束!");
        }
        $log = [
            'title' => $raffle['title'],
            'prize' => $raffle['prize'],
            'live_id' => $raffle['live_id'],
            'live_log_id' => $raffle['live_log_id'],
            'user_id' => $userId,
            'raffle_id' => $raffle['id']
        ];
        $hasRaffleLog = LiveRaffleLog::where('raffle_id','=',$data['raffles_id'])->where('user_id','=',$userId)->select('id')->first();
        if($hasRaffleLog) {
            return $this->resFailed(701,"已参与活动!");
        }
        LiveRaffleLog::create($log);
        return $this->resSuccess();
    }

    public function goodsAll(Request $request)
    {
        $goodids = json_decode($request->goods_id,1);
        $lists = $goods = Goods::whereIn('id', $goodids)->where('goods_state','=','1')->get();
        return $this->resSuccess([
            'data' => $lists
        ]);
    }
}