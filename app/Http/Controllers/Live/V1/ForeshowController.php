<?php
/**
 * @Filename        ForeshowController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Live\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Live\BaseController;
use ShopEM\Http\Requests\Live\ForeshowRequest;
use ShopEM\Models\Goods;
use ShopEM\Models\Foreshows;
use ShopEM\Repositories\ForeshowRepository;
use ShopEM\Services\Live\ForeshowService;
use ShopEM\Services\WeChatMini\CreateQrService;

class ForeshowController extends BaseController
{

    /**
     * 添加预告
     *
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(ForeshowRequest $request)
    {
        $data = $request->only('img_url','title','introduce','start_at','goodsids');

        $shop_id = $this->user->shop_id;
        $live_id = $this->user->live_id;
        $service = new CreateQrService();
        #$qrimg['img_url'] = $service->GetWxQr('','/live/pages/lives/lives?liveid='.$live_id.'&shopid='.$shop_id);
        $qrimg['img_url'] = $service->GetWxQr('','/live/pages/lives/advance?liveid='.$live_id);
        #$ForeshowService = new ForeshowService();
        #$img = $ForeshowService->poster($data,'');
        $data['wechat'] = $qrimg['img_url'];
        #$data['start_at'] = date('Y-m-d H:i:s',$data['start_at']);
        $ids = json_decode($data['goodsids'],1);
        if(!is_array($ids)){
            return $this->resFailed(406);
        }

        $live_id = $this->user->live_id;
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
        $data['shop_id'] = $shop_id;
        $data['live_id'] = $live_id;

        Foreshows::create($data);
        return $this->resSuccess();
    }

    /**
     * 预告列表
     *
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(ForeshowRepository $repository,Request $request)
    {
        $shop_id = $this->user->shop_id;
        $live_id = $this->user->live_id;
        $data['shop_id'] = $shop_id;
        $data['live_id'] = $live_id;
        $lists = $repository->list($data);
       # $lists['wechat_path'] = 'live/pages/lives/lives?liveid='.$value['live_id'];
        foreach ($lists as $key => $value) {
            $lists[$key]['wechat_img'] = $value['wechat'];
            $lists[$key]['wechat_path'] = 'live/pages/lives/lives?liveid='.$value['live_id'].'&shopid='.$shop_id;
            unset( $value['wechat']);
        }
        return $this->resSuccess([
            'lists' => $lists,
        ]);
    }

    /**
     * 删除预告
     *
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $shop_id = $this->user->shop_id;
        $live_id = $this->user->live_id;
        $foreshow = Foreshows::where('live_id', $live_id)->where('id', $request->id)->where('shop_id', $shop_id)->first();

        if (empty($foreshow)) {
            return $this->resFailed(406);
        }
        return $this->resSuccess($foreshow->delete());
    }

    /**
     * 预告详情
     *
     * @Author linzhes
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $shop_id = $this->user->shop_id;
        $live_id = $this->user->live_id;
        $foreshow = Foreshows::where('live_id', $live_id)->where('id', $request->id)->where('shop_id', $shop_id)->first();

        if (empty($foreshow)) {
            return $this->resFailed(406);
        }

        $ids = json_decode($foreshow['goodsids'],1);
        $foreshow['goods'] = Goods::whereIn('id',$ids)->get();

        return $this->resSuccess([
            'data' => $foreshow
        ]);
    }

    /**
     * 修改预告
     *
     * @Author linzhe
     * @param UserAccountRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $shop_id = $this->user->shop_id;
        $live_id = $this->user->live_id;
        $foreshow = Foreshows::where('live_id', $live_id)->where('id', $request->id)->where('shop_id', $shop_id)->first();

        if (empty($foreshow)) {
            return $this->resFailed(406);
        }

        $data = $request->only('id','img_url','title','introduce','start_at','goodsids');
        $ids = json_decode($data['goodsids'],1);

        if(!is_array($ids)){
            return $this->resFailed(406);
        }
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
                return $this->resFailed(406,"商品ID ".$id." 不存在");
            }
        }
        $data['shop_id'] = $shop_id;
        $data['live_id'] = $live_id;
        return $this->resSuccess($foreshow->update($data));
    }

}