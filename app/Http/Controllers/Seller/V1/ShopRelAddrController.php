<?php
/**
 * Created by lanlnk
 *
 * @brief 店铺回寄地址模块
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-03-25
 * @Time: 17:57
 */
namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Http\Requests\Seller\ShopRelAddrRequest;
use ShopEM\Models\ShopRelAddr;
use ShopEM\Repositories\ShopRelAddrRepository;
use Illuminate\Support\Facades\DB;


class ShopRelAddrController extends BaseController
{


    /**
     * 地址列表
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request  , ShopRelAddrRepository $shopRelAddrRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] =  $input_data['per_page'] ?? config('app.per_page');
        $input_data['shop_id'] = $this->shop->id;

        $lists = $shopRelAddrRepository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $shopRelAddrRepository->listShowFields()
        ]);

    }


    /**
     * 添加地址
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ShopRelAddrRequest $request)
    {
        $input_data = $request->only('address','is_default','name','tel');
        $input_data['shop_id'] = $this->shop->id;
        $input_data['is_default'] = $input_data['is_default'] ?? 0;

        DB::beginTransaction();
        try
        {
            if($input_data['is_default'] == 1)
            {
                $update_data['is_default'] = 0;
                ShopRelAddr::where('shop_id',$input_data['shop_id'])->update($update_data);

            }
            ShopRelAddr::create($input_data);
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();

    }

    /**
     * 删除地址
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $id = $request->id ?? 0;
        if ($id <= 0) {
            return $this->resFailed(414);
        }

//        $addr = ShopRelAddr::find($id);
        $addr = ShopRelAddr::where('id', $id)->where('shop_id', $this->shop->id)->first();
        if (empty($addr))
        {
            return $this->resFailed(701);
        }

        try
        {
            ShopRelAddr::destroy($id);
        }
        catch (\Exception $e)
        {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();

    }

    /**
     * 更新地址
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShopRelAddrRequest $request)
    {

        $input_data = $request->only('id','address','is_default','name','tel');
        $input_data['shop_id'] = $this->shop->id;

        $id = intval($input_data['id']) ?? 0;
        if ($id <= 0) {
            return $this->resFailed(414);
        }

//        $addr = ShopRelAddr::find($id);
        $addr = ShopRelAddr::where('id', $id)->where('shop_id', $this->shop->id)->first();
        if (empty($addr)) {
            return $this->resFailed(701);
        }

        DB::beginTransaction();
        try
        {
            if($input_data['is_default'] == 1)
            {
                $update_data['is_default'] = 0;
                $addr->where('shop_id',$input_data['shop_id'])->update($update_data);
            }

            $addr->update($input_data);
            DB::commit();

        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();

    }

    /**
     * 地址详情
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $id = $request->id ?? 0;
        if ($id <= 0) {
            return $this->resFailed(414);
        }

//        $detail = ShopRelAddr::find($id);
        $detail = ShopRelAddr::where('id', $id)->where('shop_id', $this->shop->id)->first();
        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 默认地址开关
     * @return \Illuminate\Http\JsonResponse
     */
    public function set(Request $request)
    {

        $input_data = $request->only('id', 'is_default');
        $input_data['shop_id'] = $this->shop->id;

        $id = intval($input_data['id']) ?? 0;
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $addr = ShopRelAddr::where('shop_id',$input_data['shop_id'])->get();
        if (empty($addr))
        {
            return $this->resFailed(701);
        }

        DB::beginTransaction();
        try
        {
            if($input_data['is_default'] == 1)
            {
                $update_data['is_default'] = 0;
                ShopRelAddr::where('shop_id',$input_data['shop_id'])->update($update_data);
            }

            //修改地址状态
            $update_data['is_default'] = $input_data['is_default'];
            ShopRelAddr::where('id',$input_data['id'])->where('shop_id',$input_data['shop_id'])->update($update_data);
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();

    }


}
