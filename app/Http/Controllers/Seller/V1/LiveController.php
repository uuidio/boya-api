<?php
/**
 * @Filename LiveController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe <lz@mlinzhe.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use ShopEM\Http\Requests\Seller\LiveRequest;
use ShopEM\Models\Lives;
use ShopEM\Models\LiveUsers;
use ShopEM\Repositories\LiveUsersRepository;
use ShopEM\Services\Live\ImService;
use ShopEM\Models\AssistantUsers;
use ShopEM\Repositories\AssistantUsersRepository;
use ShopEM\Repositories\LivesLogRepository;
use ShopEM\Repositories\LiveRebroadcastRepository;
use ShopEM\Models\LiveRebroadcast;

class LiveController extends BaseController
{

    /**
     * 添加主播
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addAnchor(LiveRequest $request)
    {

        $live = $request->only('login_account', 'password');
        $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|19[0-9]{1}[0-9]{8}$|16[0-9]{1}[0-9]{8}$/";

        if(!preg_match($chars, $live['login_account']))
        {
            return $this->resFailed(600, '手机号格式错误');
        }
        $shop_id = $this->shop->id;
        $hasPassword = preg_match('/^(\w*(?=\w*\d)(?=\w*[A-Za-z])\w*){8,16}$/', $live['password']);
        if(!$hasPassword) {
            return $this->resFailed(702,'8-16位字符（英文/数字/符号）至少两种或下划线组合');
        }
        $live['password'] = bcrypt($live['password']);
        $live['mobile'] = $live['login_account'];
        $live['shop_id'] = $shop_id;
        #$hasLive = Lives::where('shop_id', $shop_id)->first();
//        if(empty($hasLive))
//        {
//            return $this->resFailed(402, '直播间不存在');
//        }
//        $userLive = LiveUsers::where('shop_id', $shop_id)->where('live_id', $hasLive->id)->first();
//        if(!empty($userLive))
//        {
//            return $this->resFailed(402, '直播间已关联主播');
//        }

        DB::beginTransaction();
        try {
            #$live['live_id'] = $hasLive->id;
           $LiveAccount = LiveUsers::create($live);
//            $accid = md5($live['mobile'].'live'.time());
//            $service = new ImService();
//            $imInfo = $service->createUser($accid);
//            if($imInfo['code'] != '200') {
//                throw new \LogicException('添加失败，请联系管理员');
//            }
//
//            $chatroomData = $service->createChatroom(['creator'=>$imInfo['info']['accid'],'name'=>$hasLive['number']]);
//            if($chatroomData['code'] != '200') {
//                throw new \LogicException('添加失败，请联系管理员');
//            }
//            $service->chatroomToggle(['roomid'=>$chatroomData['chatroom']['roomid'],'operator'=>$imInfo['info']['accid'],'valid'=>'false']);
//            $imData = [
//                'im_token' =>  $imInfo['info']['token'],
//                'accid' =>  $imInfo['info']['accid'],
//                'roomid' => $chatroomData['chatroom']['roomid'],
//                'im_valid' => '0'
//            ];
//            Lives::where('id', $hasLive->id)->update($imData);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->resFailed(702,$exception->getMessage());
        }
        return $this->resSuccess();
    }

    public function anchorPassword(Request $request)
    {
        $data = $request->only('password', 'id');
        $shop_id = $this->shop->id;;
        $hasUser = LiveUsers::where('shop_id','=',$shop_id)->where('id','=',$data['id'])->select('id')->first();
        if(empty($hasUser)) {
            return $this->resFailed(702,'非法操作');
        }
        $hasPassword = preg_match('/^(\w*(?=\w*\d)(?=\w*[A-Za-z])\w*){8,16}$/', $data['password']);
        if(!$hasPassword) {
            return $this->resFailed(702,'8-16位字符（英文/数字/符号）至少两种或下划线组合');
        }
        $password = bcrypt($data['password']);
        $result = LiveUsers::where('id','=',$data['id'])->update(['password'=>$password]);
        if(!$result){
            return $this->resFailed(402, '修改密码失败');
        }
        return $this->resSuccess();
    }


    /**
     * 主播列表
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listAnchor(LiveUsersRepository $repository, Request $request)
    {

        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $lists = LiveUsers::where('shop_id','=',$data['shop_id'])->select('login_account','img_url','username','live_id','created_at')->get();
        $lists = $repository->search($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 助理添加
     *
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addAssistant(Request $request)
    {
        $data = $request->only('login_account', 'password');

        $shop_id = $this->shop->id;
        $live['password'] = bcrypt($data['password']);
        $live['login_account'] = $data['login_account'];
        $live['shop_id'] = $shop_id;

        $anchor = LiveUsers::where('shop_id', $shop_id)->select('id')->first();
        $live['anchor_id'] = $anchor['id'];
        $hasLive = Lives::where('shop_id', $shop_id)->first();
        $roomid = $hasLive['roomid'];
        if(empty($hasLive))
        {
            return $this->resFailed(402, '直播间不存在');
        }
        $live['live_id'] = $hasLive->id;

        $service = new ImService();
        $accid = md5($live['login_account'].'assistant'.time());
        $imInfo = $service->createUser($accid);

        if($imInfo['code'] != '200') {
            throw new \LogicException('添加失败，请联系管理员');
        }
        $role = [
            'roomid' => $roomid,
            'operator' => $hasLive['accid'],
            'target' => $accid,
            'opt' => '1',
            'optvalue' => 'true'
        ];
        $service->chatroomToggle(['roomid'=>$hasLive['roomid'],'operator'=>$hasLive['accid'],'valid'=>'true']);
        $imRole = $service->setMemberRole($role);
        $service->chatroomToggle(['roomid'=>$hasLive['roomid'],'operator'=>$hasLive['accid'],'valid'=>'false']);
        if($imRole['code'] != '200') {
            throw new \LogicException('添加失败，请联系管理员');
        }
        $live['accid'] = $accid;
        $live['roomid'] = $roomid;
        $live['im_token'] = $imInfo['info']['token'];
        AssistantUsers::create($live);
        return $this->resSuccess();
    }

    /**
     * 助理列表
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listAssistant(AssistantUsersRepository $repository, Request $request)
    {

        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
       # $lists = AssistantUsers::where('shop_id','=',$data['shop_id'])->select('login_account','img_url','username','live_id','created_at')->get();
        $lists = $repository->search($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 助理密码修改
     *
     * @Author linzhe
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assistantPassword(Request $request)
    {
        $data = $request->only('password', 'id');
        $shop_id = $this->shop->id;;
        $hasUser = AssistantUsers::where('shop_id','=',$shop_id)->where('id','=',$data['id'])->select('id')->first();
        if(empty($hasUser)) {
            return $this->resFailed(702,'非法操作');
        }
        $hasPassword = preg_match('/^(\w*(?=\w*\d)(?=\w*[A-Za-z])\w*){8,16}$/', $data['password']);
        if(!$hasPassword) {
            return $this->resFailed(702,'8-16位字符（英文/数字/符号）至少两种或下划线组合');
        }
        $password = bcrypt($data['password']);
        $result = AssistantUsers::where('id','=',$data['id'])->update(['password'=>$password]);
        if(!$result){
            return $this->resFailed(402, '修改密码失败');
        }
        return $this->resSuccess();
    }

    /**
     * 直播统计
     *
     * @Author linzhe
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        $repository = new LivesLogRepository();
        $data['shop_id'] = $this->shop->id;;
        $lists = $repository->list($data, 10);
        return $this->resSuccess([
            'lists' =>  $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 转播列表
     *
     * @Author linzhe
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rebroadcastList(Request $request)
    {
        $repository = new LiveRebroadcastRepository();
        $data['shop_id'] = $this->shop->id;;
        $lists = $repository->list($data,10);
        return $this->resSuccess([
            'lists' =>  $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 转播状态操作
     *
     * @Author linzhe
     * @param ShopRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rebroadcastStatus(Request $request)
    {
        $data['shop_id'] = $this->shop->id;
        $id = $request->id;
        $status = $request->status;
        $rebroadcast = LiveRebroadcast::find($id);

        if($status === '1') {
            Lives::where('id',$rebroadcast->live_id)->update(['rebroadcasts_id'=>$rebroadcast['rebroadcasts_live']]);
        }else{
            Lives::where('id',$rebroadcast->live_id)->update(['rebroadcasts_id'=>'0']);
        }
        $rebroadcast->update(['rebroadcasts_status'=>$status]);

        return $this->resSuccess();
    }

    public function anchorAccount(Request $request)
    {
        $data = $request->only('mobile', 'id');
        $shop_id = $this->shop->id;
        $chars = "/^13[0-9]{1}[0-9]{8}$|15[0-9]{1}[0-9]{8}$|18[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|17[0-9]{1}[0-9]{8}$|19[0-9]{1}[0-9]{8}$|16[0-9]{1}[0-9]{8}$/";

        if(!preg_match($chars, $data['mobile']))
        {
            return $this->resFailed(600, '手机号格式错误');
        }
        $hasUser = LiveUsers::where('mobile','=',$data['mobile'])->select('id')->first();
        if($hasUser) {
            return $this->resFailed(702,'手机号已存在');
        }

        $result = LiveUsers::where('shop_id','=',$shop_id)->update(['mobile'=>$data['mobile'],'login_account'=>$data['mobile']]);
        if(!$result){
            return $this->resFailed(402, '修改账号失败');
        }
        return $this->resSuccess();
    }
}