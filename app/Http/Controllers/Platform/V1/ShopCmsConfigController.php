<?php

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Models\Shop;
use ShopEM\Models\ShopCmsConfig;
use ShopEM\Http\Controllers\Platform\BaseController;

class ShopCmsConfigController extends BaseController
{
    /**
     * [list 列表]
     * @Author mssjxzw
     * @param  string  $value [description]
     * @return [type]         [description]
     */
    public function list(Request $request)
    {
        $model = new ShopCmsConfig();
        if ($request->filled('keyword')) {
            $shop = Shop::where('shop_name','like',$request->keyword)->get()->toArray();
            $shop_ids = array_column($shop, 'id');
            $model = $model->whereIn('shop_id',$shop_ids);
        }
        if ($request->filled('size')) {
            $size = $request->size;
        }else{
            $size = 10;
        }
        $list = $model->orderBy('id', 'desc')->paginate($size);
        return $this->resSuccess([
            'lists' => $list,
            'field' => [
                ['dataIndex' => 'id', 'title' => 'ID'],
                ['dataIndex' => 'shop_info.shop_name', 'title' => '店铺'],
                ['dataIndex' => 'secret', 'title' => '密钥'],
                ['dataIndex' => 'shop_no', 'title' => '店铺编码'],
            ],
        ]);
    }

    /**
     * [saveData 保存数据]
     * @Author mssjxzw
     * @param  string  $value [description]
     * @return [type]         [description]
     */
    public function saveData(Request $request)
    {
        if ($request->filled('shop_id','secret','shop_no')) {
            $data = $request->only('shop_id','secret','shop_no');
            $check = ShopCmsConfig::where('shop_id',$request->shop_id)->first();
            if ($request->filled('id')) {
                $obj = ShopCmsConfig::find($request->id);
                if ($obj) {
                    if ($check && $obj->shop_id !== $check->shop_id) {
                        return $this->resFailed(500,'已有该店铺设置');
                    }
                    foreach ($data as $key => $value) {
                        $obj->$key = $value;
                    }
                    $obj->save();
                }else{
                    return $this->resFailed(500,'无此数据');
                }
            }else{
                if ($check) {
                    return $this->resFailed(500,'已有该店铺设置');
                }
                ShopCmsConfig::create($data);
            }
        }else{
            return $this->resFailed(414,'参数不全');
        }
        return $this->resSuccess();
    }

    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = ShopCmsConfig::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    public function del($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $activity = ShopCmsConfig::find($id);
        if (!$activity) {
            return $this->resFailed(701,'没有此数据');
        }
        try {
            ShopCmsConfig::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }
}
