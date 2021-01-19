<?php
/**
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-25
 * @Time: 14:16
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Repositories\ShopAttrRepository;
use Illuminate\Http\Request;
use ShopEM\Models\Shop;
use ShopEM\Models\ShopAttr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ShopAttrController extends BaseController
{

    protected $shopAttrRepository;

    public function __construct(ShopAttrRepository $shopAttrRepository)
    {
        $this->shopAttrRepository = $shopAttrRepository;
    }


    /**
     * 获取所有店铺列表
     * @return mixed
     */
    public function lists(Request $request, ShopAttrRepository $shopAttrRepository )
    {
        $param = $request->all();
        $param['per_page'] = config('app.per_page');

        $lists = $shopAttrRepository->search($param);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $shopAttrRepository->listShowFields(),
        ]);
    }

    /**
     * 配置分销功能
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deploy(Request $request)
    {
        $param = $request->only('shop_id', 'promo_person');

        $data['promo_person'] = $param['promo_person'] ?? 0;
        if($data['promo_person'] == 1)
        {
            $data['promo_good'] = 1;
        }
        if($data['promo_person'] == 0)
        {
            $data['promo_good'] = 0;
        }
        $data['shop_id'] = $param['shop_id'];

        if(!Shop::where('id', $data['shop_id'])->exists())
        {
            return $this->resFailed(414, "店铺不存在!");
        }

        if ($data['shop_id'] <= 0)
        {
            return $this->resFailed(414, "参数错误!");
        }

        try
        {
            if(!ShopAttr::where('shop_id', $data['shop_id'])->exists())
            {
                ShopAttr::create($data);
            }else {
                ShopAttr::where('shop_id', intval($data['shop_id']))->update($data);
            }
            return $this->resSuccess();
        }
        catch (\Exception $e)
        {
            Log::error($e->getMessage());
            return $this->resFailed(600,$e->getMessage());
        }

    }

    /**
     * 控制店铺推广
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function controlPromo(Request $request)
    {

//        if(!isset($request->id) || empty($request->id)){
//            return $this->resFailed(414,"参数错误");
//        }
        if(!isset($request->shop_id) || empty($request->shop_id)){
            return $this->resFailed(414,"参数错误");
        }

        //$msg_text = '操作'.json_encode($request->id);
        $msg_text = '操作推广'.json_encode($request->shop_id);

        DB::beginTransaction();
        try
        {
            if($request->promo_person){
                //ShopAttr::whereIn('id', $request->id)
                ShopAttr::whereIn('shop_id', $request->shop_id)
                    ->update(['promo_person' => $request->promo_person,'promo_good' => 1]);
            }
            else
            {
                //ShopAttr::whereIn('id', $request->id)
                ShopAttr::whereIn('shop_id', $request->shop_id)
                    ->update(['promo_person' => $request->promo_person,'promo_good' => 0]);
            }
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text.'失败', 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text.'成功', 1);
        return $this->resSuccess();
    }

}
