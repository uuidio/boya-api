<?php
/**
 * @Filename        GoodsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Jobs\DownloadLogAct;
use ShopEM\Models\DownloadLog;
use ShopEM\Models\Goods;
use ShopEM\Models\GmPlatform;
use ShopEM\Repositories\GoodsRepository;
use ShopEM\Repositories\GoodsStockLogsListRepository;
use Illuminate\Support\Facades\Cache;


class GoodsController extends BaseController
{
    /**
     * 商品列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,GoodsRepository $repository)
    {
        $data = $request->all();
        
        $data['use_state'] = 20;
        $use_gm = true;
        // 自营店选择甄选商品的时候显示所有项目下的商品
        if (GmPlatform::gmSelf() == $this->GMID && isset($data['is_fit_up']) && $data['is_fit_up'] > 0) {
            $use_gm = false;
        }
        
        if ($use_gm) {
            $data['gm_id'] = $this->GMID;
        }
        
        $lists = $repository->listItems($data);
        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 上下架商品
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateState(Request $request)
    {

        if(!isset($request->goods_ids) || empty($request->goods_ids)){
            return $this->resFailed(414,"参数错误");
        }
        $msg = '';
        if($request->state=='0'){
            $msg = '下架';
        }else {
            $msg = '上架';
        }
        $msg_text = $msg."商品id为".json_encode($request->goods_ids);
//        $msg_text="上架商品id为".json_encode($request->goods_ids);

        DB::beginTransaction();
        try {
            //删除首页挂件缓存
            $page = 'index_fit';
            $cache_key = 'CONFIGITEMS_INDEX_PAGE_'.$page.'_GM_'.$this->GMID;
            Cache::forget($cache_key);

            Goods::whereIn('id', $request->goods_ids)
                ->update(['goods_state' => $request->state]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    /**
     * [selfGoods 自营商品]
     * @Author mssjxzw
     * @param  string  $value [description]
     * @return [type]         [description]
     */
    public function selfList(Request $request,GoodsRepository $repository)
    {
        $data = $request->all();
        $data['use_state'] = 20;
        $lists = $repository->listItems($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }


    /**
     * 商品库存日志
     * @Author hfh_wind
     * @param Request $request
     * @param GoodsStockLogsListRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function GoodsStockLogsList(Request $request,GoodsStockLogsListRepository $repository)
    {
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $repository->listItems($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 商品导出
     *
     * @Author Huiho
     * @param GoodsRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function goodDown(Request $request,GoodsRepository $repository)
    {
        /*
         *
         * 之前的导出
         * */
//        $data = $request->all();
//
//        $data['use_state'] = 20;
//        $use_gm = true;
//        // 自营店选择甄选商品的时候显示所有项目下的商品
//        if (GmPlatform::gmSelf() == $this->GMID && isset($data['is_fit_up']) && $data['is_fit_up'] > 0) {
//            $use_gm = false;
//        }
//
//        if ($use_gm) {
//            $data['gm_id'] = $this->GMID;
//        }
//
//        $lists = $repository->listItems($data , false , 1);
//        $title = $repository->downLstFields();
//
//        $return['goods']['tHeader']= array_column($title,'title'); //表头
//        $return['goods']['filterVal']= array_column($title,'dataIndex'); //表头字段
//        $return['goods']['list']= $lists; //表头
//
//        return  $this->resSuccess($return);

        /*
       *
       * 之前的导出
       * */


        $data = $request->all();

        $data['use_state'] = 20;
        $use_gm = true;
        // 自营店选择甄选商品的时候显示所有项目下的商品
        if (GmPlatform::gmSelf() == $this->GMID && isset($data['is_fit_up']) && $data['is_fit_up'] > 0) {
            $use_gm = false;
        }

        if ($use_gm) {
            $data['gm_id'] = $this->GMID;
        }

        if (isset($data['s'])) {
            unset($data['s']);
        }

        $insert['type'] = 'Goods';
        $insert['desc'] = json_encode($data);
        $insert['gm_id']=$data['gm_id'];

        $res = DownloadLog::create($insert);

        $return['log_id'] = $res['id'];
        //$data['log_id'] = 6;

        DownloadLogAct::dispatch($return);

        return $this->resSuccess('导出中请等待!');
    }

    
}