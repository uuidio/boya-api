<?php

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Models\ShopZiti;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;

class ShopZitiController extends BaseController
{
    /**
     * 店铺自提地址列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists()
    {
        $lists = ShopZiti::where('shop_id',$this->shop->id)->orderBy('id', 'desc')->get();
        return $this->resSuccess([
            'lists' => $lists,
            'field' => [
                ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
                ['key' => 'address', 'dataIndex' => 'address', 'title' => '地址'],
                ['key' => 'statue', 'dataIndex' => 'statue', 'title' => '是否可用'],
            ],
        ]);
    }

    /**
     * [store 新增自提地址]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function store(Request $request)
    {
        $data = $request->only('address');
        if (!$request->filled('address')) {
            return $this->resFailed(414, '请输入地址');
        }
        try {
            $data['shop_id'] = $this->shop->id;
            ShopZiti::create($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }

    /**
     * [edit 修改自提地址]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function edit(Request $request)
    {
        $data = $request->only('address','id','statue');
        if (!$request->filled('id')) {
            return $this->resFailed(414, '参数不全');
        }
        $info = ShopZiti::find($request->id);
        if (!$info) {
            return $this->resFailed(500, '无此数据');
        }
        if ($request->filled('address')) {
            $info->address = $request->address;
        }
        if ($request->filled('statue')) {
            $info->statue = $request->statue;
        }
        $info->save();
        return $this->resSuccess();
    }

    /**
     * [del 删除数据]
     * @Author mssjxzw
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function del(Request $request)
    {
        if ($request->filled('id')) {
            $id = $request->id;
        }else{
            return $this->resFailed(414, '参数不全');
        }
        if (is_string($id)) {
            $id = implode(',',$id);
        }
        foreach ($id as $key => $value) {
            $info = ShopZiti::find($value);
            if ($info) {
                $info->delete();
            }
        }
        return $this->resSuccess();
    }
}
