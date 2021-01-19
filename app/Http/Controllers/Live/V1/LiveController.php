<?php
/**
 * @Filename        LiveController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Live\V1;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Live\BaseController;
use ShopEM\Http\Requests\Live\LiveRequest;
use ShopEM\Repositories\GoodsRepository;
use ShopEM\Models\Lives;
use ShopEM\Services\Live\LiveAnchorService;
use ShopEM\Models\Goods;
use ShopEM\Services\Live\ImService;
use Illuminate\Support\Facades\DB;
use ShopEM\Services\Live\LiveService;
use ShopEM\Models\LivesLog;
use ShopEM\Models\LiveUsers;
use Illuminate\Support\Facades\Redis;
use ShopEM\Repositories\LivesLogRepository;
use ShopEM\Models\UserShopFavorite;
use ShopEM\Models\AssistantUsers;

class LiveController extends BaseController
{

    /**
     * 开始直播
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function begin(LiveRequest $request)
    {

        $plan = $request->only('title', 'img_url','record');
        $img = $plan['img_url'];
        $liveId = $this->user->live_id;
        $shopId = $this->user->shop_id;
        $userInfo = LiveUsers::where('id','=',$this->user->id)->select('id','username','img_url')->first();
        $plan['username'] = $userInfo['username'];
        $plan['user_img'] = $userInfo['img_url'];

        DB::beginTransaction();
        try {
            $Lives = Lives::find($liveId);
            if(empty($Lives)) {
                throw new \LogicException('找不到数据');
            }
            if($Lives['live_status'] == '1') {
                throw new \LogicException('正在直播中');
            }
            $favoriteCount = UserShopFavorite::where('shop_id','=',$shopId)->count();
            Redis::set('collect'.$liveId,$favoriteCount);
            $plan['streamname'] = $Lives['streamname'];
            $pushUrl = LiveAnchorService::getPushUrl($plan);
            if($plan['record'] == 'true') {
                $plan['record'] = '2';
            }else{
                $plan['record'] = '1';
            }
            $data['id'] = $liveId;
            $data['live_status'] = 1;
            $data['im_valid'] = 1;
            $data['title'] = $plan['title'];
            $data['img_url'] = $img;
            $imService = new ImService();
            $chatroomAddr = $imService->roomBegin(['roomid'=>$Lives['roomid'],'accid'=>$Lives['accid']]);
            $startAt = date("Y-m-d H:i:s");
            $log = ['live_id'=>$liveId,'title'=>$plan['title'],'surface_img'=>$img,'type'=>$plan['record'],'start_at'=>$startAt,'limit_goods'=>$Lives['goodids'],'shop_id'=>$this->user->shop_id];
            LivesLog::create($log);
            $Lives->update($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        $result['notice'] = $Lives['rollitle'];
        $result['live_id'] = $liveId;
        $result['push_url'] = $pushUrl;
        $result['roomid'] = $Lives['roomid'];
        $result['accid'] = $Lives['accid'];
        $result['im_token'] = $Lives['im_token'];
        $result['im_addr'] = $chatroomAddr;
        return $this->resSuccess($result);
    }

    /**
     * 关闭直播
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(LiveRequest $request)
    {
        #exit();
        $liveId = $this->user->live_id;
        $shopId = $this->user->shop_id;
        try {
            $Lives = Lives::find($liveId);
            if(empty($Lives)) {
                return $this->resFailed(701,"找不到数据!");
            }
            $log = LivesLog::where('live_id','=',$liveId)->select('id','type','start_at','playback','task_id')->orderBy('id','desc')->first();
            $vodAddr = null;
            if($log['type'] == '2'){
                $timeStart = strtotime($log['start_at']);
                $dataStart = date("c", $timeStart);
                $dataEnd = date("c", time());
                $LiveService = new LiveService();
                if($log['task_id']) {
                    $LiveService->stopLiveRecord(['streamname'=>$Lives['streamname'],'taskid'=>$log['task_id']]);
                    $search = [
                        'streamname' => $Lives['streamname'],
                        'start_time' => $dataStart,
                        'end_time' => $dataEnd,
                    ];
                    $i=0;
                    while($i<5000){
                        $vodAddr = $LiveService->searchVod($search);
                        if($vodAddr){
                            break;
                        }
                        $i++;
                    }
                }
            }
            $vodAddr = $vodAddr ? $vodAddr : null ;
            $service = new ImService();
            $historyFavoriteCount = UserShopFavorite::where('shop_id','=',$shopId)->count();
            $chcheFavoriteCount = Redis::get('collect'.$liveId);
            $favoriteCount = $historyFavoriteCount - $chcheFavoriteCount;
            if($favoriteCount >= 0) {
                $favoriteCount = $favoriteCount;
            }else{
                $favoriteCount = '0';
            }
            $likeCount = Redis::get('like'.$liveId);
            $hotCount = Redis::get('hot'.$liveId);
            $audienceJson = Redis::get('audience'.$liveId);
            if($audienceJson) {
                $audienceCount = count(json_decode($audienceJson,1));
            }else{
                $audienceCount = '0';
            }
            $chatroomAddr = $service->chatroomToggle(['roomid'=>$Lives['roomid'],'operator'=>$Lives['accid'],'valid'=>'false']);
            LivesLog::where('id','=',$log['id'])->update(['playback'=>$vodAddr,'end_at'=>date("Y-m-d H:i:s"),'like'=>$likeCount,'heat'=>$hotCount,'audience'=>$audienceCount,'collect'=>$favoriteCount]);
            $data['id'] = $liveId;
            $data['live_status'] = 0;
            $data['im_valid'] = 0;
            $data['showid'] = null;
            //删除缓存
            Redis::del('like'.$liveId);
            Redis::del('hot'.$liveId);
            Redis::del('swoole'.$liveId);
            Redis::del('audience'.$liveId);
            Redis::del('collect'.$liveId);
            $Lives->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        $data['live_id'] = $liveId;

        return $this->resSuccess();
    }

    /**
     * 录制任务
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveRecord()
    {
        $liveId = $this->user->live_id;
        $Lives = Lives::find($liveId);
        try {
            if(empty($Lives)) {
                throw new \LogicException('找不到数据');
            }
            if($Lives['live_status'] == '0') {
                throw new \LogicException('未开播不能录制');
            }
            $log = LivesLog::where('live_id','=',$liveId)->select('id','type','start_at')->orderBy('id','desc')->first();
            $plan['streamname'] = $Lives['streamname'];
            $LiveService = new LiveService();
            $re = $LiveService->createLiveRecord($Lives['streamname']);
            LivesLog::where('id','=',$log['id'])->update(['task_id'=>$re['TaskId']]);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }


    /**
     * 商品列表
     * @Author linzhe
     * @param GoodsRepository $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function goods(LiveRequest $request,GoodsRepository $repository)
    {

        $data['shop_id'] = $this->user->shop_id;
        $data['goods_state'] = '1';
        $check = $request->check;
        if($check === '1'){
            $lists = Goods::where('live_check','>','0')->where('shop_id','=',$data['shop_id'])->where('goods_state','=','1')->select('*')->get();
        }elseif ($check === '0'){
            $lists = Goods::where('live_check','<','1')->where('shop_id','=',$data['shop_id'])->where('goods_state','=','1')->select('*')->get();
        }else{
            $lists = Goods::where('shop_id','=',$data['shop_id'])->where('goods_state','=','1')->select('*')->get();
        }

        foreach($lists as $key => $value) {
            if($value['live_check']){
                $lists[$key]['live_check'] = 'true';
            }else{
                $lists[$key]['live_check'] = 'false';
            }
        }

        return $this->resSuccess([
            'lists' => $lists,
        ]);
    }

    /**
     * 保存商品
     * @Author linzhe
     * @param GoodsRepository $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveGoods(LiveRequest $request)
    {
        $ids = json_decode($request->ids,1);
        if(!$ids){
            return $this->resFailed(701, '操作失败');
        }
        $shop_id = $this->user->shop_id;
        $good = Goods::where('shop_id','=',$shop_id)->where('goods_state','=','1')->select('id')->get();
        if($good) {
            foreach ($good as $k => $v)
            {
                $goods[] = $v['id'];
            }
        }

        foreach($ids as $key => $id)
        {
            $matching = array_search($id,$goods);
            if($matching === false) {
                return $this->resFailed(701,"商品ID ".$id." 不存在");
            }
        }

        DB::beginTransaction();
        try {
            $liveId = $this->user->live_id;
            $Lives = Lives::find($liveId);
            $checkGoods = json_decode($Lives['goodids'],1);
            if($checkGoods){
                $goods = array_merge($checkGoods, $ids);
            }else{
                $goods = $ids;
            }
            $goods = array_unique($goods);
            $data['id'] = $liveId;
            $data['goodids'] = json_encode($goods);

            $Lives->update($data);
            Goods::whereIn('id',$goods)->update(['live_check'=>$liveId]);
            if($Lives['live_status'] == '1') {
                $datas['live_id'] = $liveId;
                $datas['goods_id'] = $data['goodids'];
                $datas['type'] = 'goodsAll';
                $LiveService = new LiveService();
                $LiveService->swooleSend($datas);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 删除商品
     * @Author linzhe
     * @param GoodsRepository $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delGoods(LiveRequest $request)
    {
        $ids = json_decode($request->ids,1);
        if(!$ids){
            return $this->resFailed(701, '操作失败');
        }
        $shop_id = $this->user->shop_id;
        $liveId = $this->user->live_id;
        $Lives = Lives::find($liveId);
        $checkGoods = json_decode($Lives['goodids'],1);
        foreach ($ids as $key => $value) {
            if(in_array($value,$checkGoods)){
                $key = array_search($value ,$checkGoods);
                $noCheck[] = $value;
                unset($checkGoods[$key]);
            }
        }
        $goods = array_merge($checkGoods);
        DB::beginTransaction();
        try {
            $data['id'] = $liveId;
            $data['goodids'] = json_encode($goods);
            $Lives->update($data);
            Goods::whereIn('id',$noCheck)->update(['live_check'=>'0']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * 更新悬浮商品
     *
     * @Author linzhe
     * @param GoodsRepository $request
     * @return bool
     */
    public function showUpdateGoods(LiveRequest $request)
    {
        $ids = $request->id;
        $liveId = $this->user->live_id;
        $Lives = Lives::find($liveId);
        $data['showid'] = $ids;
        $Lives->update($data);
        $datas['live_id'] = $liveId;
        $datas['goods_id'] = $ids;
        $datas['type'] = 'goods';
        $LiveService = new LiveService();
        $LiveService->swooleSend($datas);
        return $this->resSuccess();
    }

    /**
     * 展示悬浮商品
     * @Author linzhe
     * @param GoodsRepository $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function showGoods()
    {
        $liveId = $this->user->live_id;
        $Lives = Lives::find($liveId);
        $list = Goods::where('id','=',$Lives['showid'])->where('goods_state','=','1')->select('*')->get();
        return $this->resSuccess($list);
    }

    /**
     * 分享
     * @Author linzhe
     * @param GoodsRepository $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function share()
    {
        $liveId = $this->user->live_id;
        $Lives = Lives::find($liveId);
        $data['title'] = $Lives['title'];
        $data['image'] = $Lives['img_url'];
        $data['path'] = 'live/pages/lives/lives?liveid='.$liveId.'&shopid='.$Lives['shop_id'];
        $data['wechat_img'] = $Lives['wechat'];
        return $this->resSuccess([
            'data' => $data,
        ]);
    }

    /**
     * 回放列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function playback(LivesLogRepository $repository)
    {
        $liveId = $this->user->live_id;
        $data = [
            'per_page' => config('app.per_page'),
            'type'  =>  '2',
            'delete'=>  '0',
            'live_id' => $liveId
        ];
        $list = $repository->list($data,config('app.per_page'));
        return $this->resSuccess([
            'list' => $list,
        ]);
    }

    /**
     * 回放删除（非物理删除）
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delPlayback(Request $request)
    {
        $id = $request->id;
        $Live = LivesLog::find($id);
        if(empty($Live)) {
            return $this->resFailed(701,"找不到数据!");
        }
        $data['delete'] = '1';
        $Live->update($data);
        return $this->resSuccess();
    }

    /**
     * 助理列表
     *
     * @Author linzhe
     * @param Request $request
     * @return array
     */
    public function assistan(Request $request)
    {
        $liveId = $this->user->live_id;
        $assistant = AssistantUsers::where('live_id','=',$liveId)->select('login_account','img_url','username')->first();
        return $this->resSuccess([
            'data' => $assistant
        ]);

    }

    /**
     * 断流回调
     *
     * @Author linzhe
     */
    public function streamEndNotifyUrl(Request $request)
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body,1);
        Log::info('streamEnd Notice'.$body);
        if(empty($data)) {
            $code['code'] = 0;
            $recode = json_encode($code);
            #return $this->resFailed(701,"streamEnd 找不到数据!");
        }
        $live = Lives::where('streamname','=',$data['stream_id'])->select('id','shop_id')->first();
        if(empty($live)){
            $code['code'] = 0;
            $recode = json_encode($code);
            return $this->resFailed(701,"streamEnd 找不到数据!");
        }
        $live['sequence'] = $data['sequence'];
        $LiveService = new LiveService();
        $re = $LiveService->streamEnd($live);
        $code['code'] = 0;
        $recode = json_encode($code);
        echo $recode;exit;
    }

    /**
     * 录制回调
     *
     * @Author linzhe
     */
    public function recordNotifyUrl(Request $request)
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body,1);
        Log::info('recordNotify Notice'.$body);
        #$live = LivesLog::where('task_id','=',$data['task_id'])->select('id')->first();
        $live = Lives::where('streamname','=',$data['stream_id'])->select('id','shop_id','live_status','streamname')->first();
        $liveId = $live['id'];
        $log = LivesLog::where('live_id','=',$liveId)->select('id','type','start_at')->orderBy('id','desc')->first();
//        if(!$live['id']){
//            return $this->resFailed(701,"recordNotify 找不到数据!");
//        }
        $re = LivesLog::where('id','=',$log['id'])->update(['playback'=>$data['video_url']]);
        #if($re){
            $code['code'] = 0;
            $code = json_encode($code);
            echo $code;
        #}
    }

    /**
     * 推流回调
     *
     * @Author linzhe
     */
    public function treamBeginNotifyUrl(Request $request)
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body,1);
        $live = Lives::where('streamname','=',$data['stream_id'])->select('id','shop_id','live_status','streamname')->first();
        Log::info('streamBegin Notice'.$body);
        if(empty($live)){
            return $this->resFailed(701,"treamBegin 找不到数据!");
        }
        $liveId = $live['id'];
        try {
            if($live['live_status'] == '0') {
                throw new \LogicException('未开播不能录制');
            }
            $log = LivesLog::where('live_id','=',$liveId)->select('id','type','start_at')->orderBy('id','desc')->first();
            $plan['streamname'] = $live['streamname'];
            #$LiveService = new LiveService();
            #$re = $LiveService->createLiveRecord($live['streamname']);
            #LivesLog::where('id','=',$log['id'])->update(['task_id'=>$re['TaskId'],'sequence'=>$data['sequence']]);
            LivesLog::where('id','=',$log['id'])->update(['sequence'=>$data['sequence']]);
                $code['code'] = 0;
                $code = json_encode($code);
                echo $code;

        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
    }

    /**
     * 真实观看人数获取
     *
     * @Author linzhe
     */
    public function audience()
    {
        $liveId = $this->user->live_id;
        $audienceJson = Redis::get('audience'.$liveId);
        if($audienceJson) {
            $audienceCount = count(json_decode($audienceJson,1));
        }else{
            $audienceCount = '0';
        }
        $data['audience'] = $audienceCount;
        return $this->resSuccess([
            'data' => $data
        ]);
    }

    /**
     * 强制关闭直播间
     *
     * @Author linzhe
     */
    public function closeLive()
    {
        $liveId = $this->user->live_id;
        $shopId = $this->user->shop_id;
        $Lives = Lives::find($liveId);
        Lives::where('id','=',$liveId)->update(['live_status'=>'0']);
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

        $log = LivesLog::where('live_id','=',$liveId)->select('id','type','start_at')->orderBy('id','desc')->first();
        LivesLog::where('id', '=', $log['id'])->update(['end_at' => date("Y-m-d H:i:s"), 'like' => $likeCount, 'heat' => $hotCount, 'audience' => $audienceCount, 'collect' => $favoriteCount,'live_status'=>'0']);
        //删除缓存
        Redis::del('like' . $liveId);
        Redis::del('hot' . $liveId);
        Redis::del('swoole' . $liveId);
        Redis::del('audience' . $liveId);
        Redis::del('collect' . $liveId);
        return $this->resSuccess();
    }
}
