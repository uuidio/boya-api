<?php
/**
 * @Filename        AssistantController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Live\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use ShopEM\Services\Live\ImService;
use Illuminate\Support\Facades\Auth;
use ShopEM\Http\Controllers\Live\BaseController;
use ShopEM\Traits\ProxyOauth;
use ShopEM\Models\AssistantUsers;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserAccount;
use ShopEM\Models\LiveBanned;
use ShopEM\Models\Lives;
use ShopEM\Models\LiveUsers;
use ShopEM\Models\WxUserinfo;
use Illuminate\Support\Facades\Redis;
use ShopEM\Repositories\LiveBannedsRepository;
use ShopEM\Services\Live\LiveService;
use ShopEM\Models\Goods;
use ShopEM\Http\Requests\Live\RafflesRequest;
use ShopEM\Models\LivesLog;
use ShopEM\Models\LiveRaffle;
use ShopEM\Models\LiveRaffleLog;
use ShopEM\Repositories\RaffleLogRepository;

class AssistantController extends BaseController
{
    use ProxyOauth;

    /**
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {

        $hasUser = AssistantUsers::where('login_account', $request->username)->first();

        if (empty($hasUser)) {
            return $this->resFailed(402);
        }
        $token = $this->authenticate('assistant_users');

        if (!$token) {
            return $this->resFailed(402);
        }

        return $this->resSuccess($token);
    }

    /**
     * 退出
     *
     * @Author linzhe
     * @return string
     */
    public function logout()
    {
        if (Auth::guard('assistant_users')->check()) {
            Auth::guard('assistant_users')->user()->token()->delete();
        }

        return $this->resSuccess();
    }

    /**
     * 设置禁言
     *
     * @Author linzhe
     * @return string
     */
    public function setBanned(Request $request)
    {
        $data = $request->only('accid', 'roomid');

        $hasUser = UserAccount::where('accid','=',$data['accid'])->select('id')->first();
        if(empty($hasUser))
        {
            return $this->resFailed(700, '用户不存在');
        }

        $param = [
            'roomid' => $data['roomid'],
            'operator' => $this->assistant->accid,
            'target' => $data['accid'],
            'opt' => -2,
            'optvalue' => 'true'
        ];
        DB::beginTransaction();
        try {
            $service = new ImService();
            $imRole = $service->setMemberRole($param);
            if($imRole['code'] != '200') {
                #throw new \LogicException('设置失败');
                throw new \LogicException($imRole['desc']);
            }
            $bannedUser = [
                'user_id' => $hasUser['id'],
                'live_id' => $this->assistant->live_id,
                'accid' => $data['accid'],
                'roomid' => $data['roomid'],
            ];
            LiveBanned::create($bannedUser);
            DB::commit();
        } catch (\Exception $e) {
            #$param['optvalue'] = 'false';
            #$service->setMemberRole($param);
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * 禁言列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function banned(Request $request,LiveBannedsRepository $repository)
    {
        $liveId = $this->assistant->live_id;
        #$banned = LiveBanned::find($liveId);
        $list = LiveBanned::where('live_id','=',$liveId)->get();
        foreach($list as $key => $value)
        {
            $info = WxUserinfo::where('user_id','=',$value['user_id'])->select('nickname','headimgurl')->first();
            $list[$key]['nickname'] = $info['nickname'];
            $list[$key]['headimgurl'] = $info['headimgurl'];
        }

        return $this->resSuccess([
            'list' => $list
        ]);
    }

    /**
     * 取消禁言
     *
     * @Author linzhe
     * @return bool
     */
    public function cancelBanned(Request $request)
    {
        $id = $request->id;
        $banned = LiveBanned::find($id);
        if(empty($banned)) {
            return $this->resFailed(700, '用户不存在');
        }
        $param = [
            'roomid' => $banned['roomid'],
            'operator' => $this->assistant->accid,
            'target' => $banned['accid'],
            'opt' => -2,
            'optvalue' => 'false'
        ];
        DB::beginTransaction();
        try {
            $hasLive = Lives::where('shop_id', $this->assistant->shop_id)->first();
            $service = new ImService();
            if($hasLive['live_status'] == '1') {
                $imRole = $service->setMemberRole($param);
            }else{
                $service->chatroomToggle(['roomid'=>$banned['roomid'],'operator'=>$hasLive['accid'],'valid'=>'true']);
                $imRole = $service->setMemberRole($param);
                $service->chatroomToggle(['roomid'=>$banned['roomid'],'operator'=>$hasLive['accid'],'valid'=>'false']);
            }
            if($imRole['code'] != '200') {
                #throw new \LogicException('取消失败');
                throw new \LogicException($imRole['desc']);
            }
            $banned->delete();
            DB::commit();
        } catch (\Exception $e) {
            #$param['optvalue'] = 'true';
            #$service->setMemberRole($param);
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * 修改直播间公告
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function noticeUpdate(Request $request)
    {
        $rollitle = $request->notice;
        $notice = Lives::where('id','=',$this->assistant->live_id)->update(['rollitle'=>$rollitle]);
        if(empty($notice)) {
            return $this->resFailed(700, '发布失败');
        }
        $data['live_id'] = $this->assistant->live_id;
        $data['notice'] = $rollitle;
        $data['type'] = 'notice';
        $LiveService = new LiveService();
        $LiveService->swooleSend($data);
        return $this->resSuccess();
    }

    /**
     * 获取直播间公告
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function notice(Request $request)
    {
        $notice = Lives::where('id','=',$this->assistant->live_id)->select('rollitle')->first();
        $data['live'] = $this->assistant->live_id;
        $data['notice'] = $notice['rollitle'];

        return $this->resSuccess([
            'data' => $data,
        ]);
    }

    /**
     * 获取会员信息
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $user_id = $this->assistant->id;

        try {
            $data = AssistantUsers::where('id','=',$user_id)->select('id','username','img_url')->first();
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess([
            'data' => $data
        ]);
    }

    /**
     * 修改会员信息
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function modifyUser(Request $request)
    {
        $data = $request->only('img_url', 'username');
        $user_id = $this->assistant->id;
        $accid = $this->assistant->accid;
        try {
            AssistantUsers::where('id','=',$user_id)->update($data);
            $service = new ImService();
            $service->updateUser(['accid'=>$accid,'name'=>$data['username']]);
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 进入直播间
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function live()
    {
        $userInfo = $this->assistant;
        try {
            $Lives = Lives::find($userInfo['live_id']);
            if(empty($Lives)) {
                throw new \LogicException('找不到数据');
            }
            if($Lives['live_status'] == '0') {
                throw new \LogicException('暂未开播');
            }

            $service = new ImService();
            $im_addr = $service->getAddr(['roomid'=>$userInfo['roomid'],'accid'=>$userInfo['accid'],'clienttype'=>2]);
            if($im_addr['code'] == '200') {
                $data['im_addr'] = $im_addr['addr'];
            }
            $data['roomid'] = $userInfo['roomid'];
            $data['accid'] = $userInfo['accid'];
            $data['im_token'] = $userInfo['im_token'];
            $data['live_addr'] = "rtmp://pull.jumhz.com/live/".$Lives['streamname'].'_live';
            $anchorInfo = LiveUsers::where('live_id','=',$userInfo['live_id'])->select('id','username','img_url')->first();
            $data['img_url'] = $anchorInfo['img_url'];
            $data['username'] = $anchorInfo['username'];
            $data['live_id'] = $userInfo['live_id'];
            $like_count = Redis::get('like'.$userInfo['live_id']);
            $data['count'] = $like_count ? $like_count : '0';
            $data['notice'] = $Lives['rollitle'];
            $data['wechat_img'] = $Lives['wechat'];
            $data['title'] = $Lives['title'];
            $data['image'] = $Lives['img_url'];
            $data['path'] = 'live/pages/lives/lives?liveid='.$userInfo['live_id'].'&shopid='.$userInfo['shop_id'];
            $goodids = json_decode($Lives['goodids'],1);
            if($goodids) {
                $goods = Goods::whereIn('id', $goodids)->get();
                $data['goods'] = $goods;
            }else{
                $data['goods'] = [];
            }
            if($Lives['showid']) {
                $showgoods = Goods::where('id', $Lives['showid'])->first();
                $data['goods_show'] = $showgoods;
            }

        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess([
            'data' => $data
        ]);
    }

    /**
     * 设置直播间抽奖
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rafflesAdd(RafflesRequest $request)
    {
        try {
            $data = $request->only('title','prize','number','response');
            $userInfo = $this->assistant;
            $liveId = $userInfo['live_id'];
            $Lives = Lives::find($liveId);
            if(empty($Lives)) {
                throw new \LogicException('找不到数据');
            }
            if($Lives['live_status'] == '0') {
                throw new \LogicException('暂未开播');
            }
            $log = LivesLog::where('live_id','=',$liveId)->select('id','type','start_at','playback','task_id')->orderBy('id','desc')->first();
            $data['live_id'] = $liveId;
            $data['live_log_id'] = $log['id'];
            $raffle = LiveRaffle::create($data);
            $response['time'] = $data['response'];

            $swoole['live_id'] = $this->assistant->live_id;
            $swoole['time'] = $response['time'];
            $swoole['raffles_id'] = $raffle['id'];
            $swoole['title'] = $raffle['title'];
            $swoole['number'] = $raffle['number'];
            $swoole['prize'] = $raffle['prize'];
            $swoole['type'] = 'raffles';
            $swoole['status'] = 1;
            $LiveService = new LiveService();
            $LiveService->swooleSend($swoole);
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess([
            'data' => $response
        ]);
    }

    /**
     * 开奖
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refflesResult(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->only('raffles_id');
            if(!$data['raffles_id']) {
                throw new \LogicException('找不到数据');
            }
            $raffle = LiveRaffle::where('id','=',$data['raffles_id'])->select('*')->first();
            if($raffle['status'] != '0') {
                throw new \LogicException('已开奖');
            }
            $raffleTime = strtotime($raffle['created_at']) + $raffle['response'];
            if(time() < $raffleTime) {
                throw new \LogicException('未到开奖时间');
            }
            $raffleLog = LiveRaffleLog::where('raffle_id','=',$data['raffles_id'])->select('*')->get()->toArray();
            $participationCount = count($raffleLog);
                if($participationCount > $raffle['number'])
                {
                    $win = array_rand($raffleLog,$raffle['number']);
                    if(is_array($win)) {
                        foreach($win as $key => $value) {
                            $raffleIds[] = $raffleLog[$value]['id'] ;
                        }
                        LiveRaffleLog::whereIn('id',$raffleIds)->update(['status'=>'2']);
                    }else{
                        $raffleIds = $raffleLog[$win]['id'] ;
                        LiveRaffleLog::where('id','=',$raffleIds)->update(['status'=>'2']);
                    }
                }else{
                    LiveRaffleLog::where('raffle_id','=',$data['raffles_id'])->update(['status'=>'2']);
                }
            LiveRaffleLog::where('raffle_id','=',$data['raffles_id'])->where('status','=','0')->update(['status'=>'1']);
            #更新状态后重组websocket数据
            $raffleLogNew = LiveRaffleLog::where('raffle_id','=',$data['raffles_id'])->select('*')->get()->toArray();
            $LiveService = new LiveService();
                foreach($raffleLogNew as $key => $value){
                    if($value['status'] == '1'){
                        $swoole['status'] = 0;
                    }else{
                        $swoole['status'] = 666;
                    }
                    $swoole['type'] = 'result';
                    $swoole['live_id'] = $raffle['live_id'];
                    $swoole['user_id'] = $value['user_id'];
                    $swoole['prize'] = $value['prize'];
                    $LiveService->swooleSend($swoole);
                }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * 中奖列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function raffleList(RaffleLogRepository $repository,Request $request)
    {
        $userInfo = $this->assistant;
        $liveId = $userInfo['live_id'];
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
}