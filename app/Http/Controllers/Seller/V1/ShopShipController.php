<?php

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Models\ShopShip;
use Illuminate\Http\Request;
use ShopEM\Services\ShopsShipService;
use ShopEM\Repositories\ShopShipRepository;
use ShopEM\Http\Requests\Seller\ShopShipRequest;
use ShopEM\Http\Controllers\Seller\BaseController;

class ShopShipController extends BaseController
{
    /**
     * [list 列表页]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function list(Request $request,ShopShipRepository $repository)
    {
        $data = $request->all();
        $data['shop_id'] = $this->shop->id;
        $lists = $repository->listItems($data,10);
        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * [saveData 保存数据]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function saveData(ShopShipRequest $request,ShopsShipService $service)
    {
        $data = $request->only('id', 'name', 'rules', 'type', 'add_type', 'default', 'is_proctect', 'proctect_rate', 'status');
        $data['shop_id'] = $this->shop->id;
        $check = ShopShip::where(['shop_id'=>$this->shop->id,'default'=>1])->count();
        if ($check == 0) {
            $data['default'] = 1;
        }
        if ($check == 1 && isset($data['default']) && $data['default'] == 1) {
            ShopShip::where('shop_id',$this->shop->id)->update(['default'=>0]);
        }
        $check = ShopShip::where(['name'=>$request->name,'shop_id'=>$this->shop->id])->first();
        if ($check) {
            return $this->resFailed(500,'名称（'.$check->name.'）已绑定运费模板');
        }
        if (isset($data['id']) && $data['id']) {
            $id = $data['id'];
            unset($data['id']);
            $ship = ShopShip::find($id);
            if (!$ship) {
                return $this->resFailed(701,'没有此运费模板');
            }
            ShopShip::where('id',$id)->update($data);
        }else{
            $ship = ShopShip::create($data);
        }
        return $this->resSuccess();
    }

    /**
     * [detail 详情]
     * @Author mssjxzw
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = ShopShip::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * [del 删除]
     * @Author mssjxzw
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function del($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $ship = ShopShip::find($id);
        if (!$ship) {
            return $this->resFailed(701);
        }

        try {
            ShopShip::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

    /**
     * [setDefault 设置默认模板]
     * @Author mssjxzw
     * @param  integer $id [description]
     */
    public function setDefault($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $ship = ShopShip::find($id);
        if (!$ship) {
            return $this->resFailed(701);
        }
        ShopShip::where('shop_id',$this->shop->id)->update(['default'=>0]);
        $ship->default = 1;
        $ship->save();
        return $this->resSuccess();
    }

    /**
     * [open 开启]
     * @Author mssjxzw
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function open($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $ship = ShopShip::find($id);
        if (!$ship) {
            return $this->resFailed(701);
        }
        $ship->status = 1;
        $ship->save();
        return $this->resSuccess();
    }

    /**
     * [close 关闭]
     * @Author mssjxzw
     * @param  integer $id [description]
     * @return [type]      [description]
     */
    public function close($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $ship = ShopShip::find($id);
        if (!$ship) {
            return $this->resFailed(701);
        }
        $ship->status = 0;
        $ship->save();
        return $this->resSuccess();
    }
}
