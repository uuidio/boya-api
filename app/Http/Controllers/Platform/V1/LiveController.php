<?php
/**
 * @Filename UploadController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe <lz@linzhe.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\LivesRequest;
use ShopEM\Models\Lives;
use ShopEM\Models\Config;
use ShopEM\Models\Shop;
use ShopEM\Repositories\LivesRepository;
use ShopEM\Services\WeChatMini\CreateQrService;
use ShopEM\Repositories\LivesLogRepository;
use ShopEM\Services\Live\LiveAnchorService;
use ShopEM\Models\LiveRebroadcast;
use ShopEM\Models\LiveUsers;
use ShopEM\Models\Notice;

class LiveController extends BaseController
{
    /**
     * 添加直播间
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addLive(LivesRequest $request)
    {
        $data = $request->only('title','shop_id','number','subtitle','rollitle','img_url','introduce','listorder','login_account','password','mobile','goods_serial');
        $shop = Shop::where('id', $data['shop_id'])->where('live_status', '=', '1')->count();
        if ($shop) {
            return $this->resFailed(702, '商家已存在直播间!');
        }

        $number = Lives::where('number',$data['number'])->count();
        if($number) {
            return $this->resFailed(702, '房间号已存在!');
        }

        $data['status'] = '1';
        $data['streamname'] = time();
        DB::beginTransaction();
        try {
            $live = Lives::create($data);
            $service = new CreateQrService();
            $qrimg = $service->GetWxQr('','/live/pages/lives/lives?liveid='.$live['id'].'&shopid='.$data['shop_id']);
            Lives::where('id','=',$live['id'])->update(['wechat'=>$qrimg]);
            Shop::where('id', $data['shop_id'])->update(['live_status' => '1']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 更改直播间
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLive(Request $request)
    {
        $id = intval($request->id);
        $data = $request->only('title','shop_id','number','subtitle','rollitle','img_url','introduce','listorder','login_account','password','mobile','goods_serial','status');

        DB::beginTransaction();
        try {
            $live = Lives::find($id);
            $live->update($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * 直播间列表
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listLive(Request $request ,LivesRepository $livesRepository)
    {
        $repository = new LivesRepository();
        $request['id'] = '';
        $lists = $repository->list($request->all(), 10);

        foreach ($lists as $key => $value) {
            $lists[$key]['rebroadcast_status'] = $value['rebroadcast'];
            $lists[$key]['rebroadcast'] = $value['rebroadcast'] ? '已授权' : '未授权';
            $lists[$key]['live_url'] = "http://pull.jumhz.com/live/".$value['streamname'].".flv";
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 直播间敏感词添加
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sensitive(Request $request)
    {
        $value = $request->value;
        $configMdl = new config;
        $data['group'] = 'live';
        $data['page'] = 'live';
        $data['value'] = $value;
        $hasConfig = Config::where('page', 'live')->where('group', 'live')->first();

        if (empty($hasConfig)) {
            $configMdl::create($data);
        } else {
            $hasConfig->update($data);
        }
        return $this->resSuccess();
    }

    /**
     * 直播间敏感词列表
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sensitive_edit(Request $request)
    {
        $hasConfig = Config::where('page', 'live')->where('group', 'live')->first();
        $value = $hasConfig->value;
        return $this->resSuccess([
            'lists' => $value,
        ]);
    }

    /**
     * 直播间统计
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request)
    {
        $repository = new LivesLogRepository();
        $request['null'] = true;
        $lists = $repository->list($request->all(), 10);
        return $this->resSuccess([
            'lists' =>  $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 发起转播授权
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rebroadcast(Request $request)
    {
        $id = $request->id;
        #$live = Lives::where('id',$id)->select('id','shop_id')->first();
        $repository = new LivesRepository();
        $data['id'] = $id;
        $data['status'] = '1';
        $lists = $repository->list($data, 1);
        $live = $lists[0];
        if($live['shop_type'] !== 'self') {
            return $this->resFailed(702, '非自营店铺不可授权转播!');
        }
        if($live['rebroadcast'] == 1) {
            return $this->resFailed(702, '请勿重复授权!');
        }
        $LiveAnchorService = new LiveAnchorService();
        $LiveAnchorService->rebroadcast($id);
        return $this->resSuccess();
    }

    /**
     * 取消转播授权
     *
     * @Author linzhe <lz@linzhe.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rebroadcastCancel(Request $request)
    {
        $id = $request->id;
        $live = Lives::where('id',$id)->select('id','shop_id','rebroadcast')->first();

        if($live['rebroadcast'] !== 1) {
            return $this->resFailed(702, '未授权!');
        }
        DB::beginTransaction();
        try {
            Lives::where('rebroadcasts_id',$live['id'])->update(['rebroadcasts_id'=>0]);
            Lives::where('id',$live['id'])->update(['rebroadcast'=>0]);
            LiveRebroadcast::where('rebroadcasts_live',$live['id'])->update(['rebroadcasts'=>0,'rebroadcasts_status'=>3]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * [filterExport 筛选导出订单]
     * @Author lin
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function filterExport(Request $request)
    {
        $model = new Lives();;
        if (isset($request['number'])) {
            $model = $model->where('lives.number',$request['number']);
            unset($request['number']);
        }
        $res = $model->get();
        $order_list = [];
        /*
         *组装导出表结构
         */
        foreach ($res as $key => $value) {
            $shop = Shop::where('id',$value->shop_id)->select('shop_name')->first();
            $live = LiveUsers::where('live_id',$value->id)->select('mobile')->first();
            $filter['title'] = $value->title;
            $filter['number'] = $value->number;
            $filter['streamname'] = $value->streamname;
            $filter['shop_name'] = $shop->shop_name;
            $filter['rebroadcast'] = $value->rebroadcast;
            $filter['mobile'] = $live->mobile;
            $filter_list[] = $filter;
        }
        $return['filter']['tHeader'] = ['直播间标题','直播间编号','直播流名称','店铺名称','转播状态','主播手机号'];
        $return['filter']['filterVal'] = ['title','number','streamname','shop_name','rebroadcast','mobile'];
        $return['filter']['list'] = $filter_list;
        return $this->resSuccess($return);
    }

    /**
     * 添加公告
     *
     * @Author linzhe
     */
    public function noticeAdd(Request $request)
    {
        $data = $request->only('title','notice');
        Notice::create($data);

        return $this->resSuccess();
    }

    /**
     * 编辑公告
     *
     * @Author linzhe
     */
    public function noticeSave(Request $request)
    {
        $notice = Notice::find(intval($request->id));
        if (empty($notice)) {
            return $this->resFailed(700, '数据不存在');
        }
        $notice->title = $request->title;
        $notice->notice = $request->notice;
        $notice->save();

        return $this->resSuccess();
    }

    /**
     * 公告列表
     *
     * @Author linzhe
     */
    public function noticeList(Request $request)
    {
        $data = $request->all();
        $data['per_page'] = $data['per_page']  ?? config('app.per_page');
        $repository = new \ShopEM\Repositories\NoticeRepository();
        $lists = $repository->listItems($data, 10);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }
}