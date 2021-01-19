<?php
/**
 * @Filename        GoodsRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class GoodsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'goods_name'        => 'required',
//            'goods_info'        => 'required',
            'gc_id_3'           => 'required',
            'brand_id'          => 'required',
            'goods_price'       => 'required',
            'goods_marketprice' => 'required',
//            'goods_cost'        => 'required',
//            'goods_serial'      => 'required',
            'goods_stock'       => 'required',
//            'goods_body'        => 'required',
            'goods_image'       => 'required',
            'image_list'        => 'required',

        ];
    }

    /**
     * Get rule messages.
     *
     * @author moocde <mo@mocode.cn>
     * @return array
     */
    public function messages()
    {
        return [
            'goods_name.required'        => '请填写商品名称',
//            'goods_info.required'        => '请填写商品简介',
            'gc_id_3.required'           => '请选择正确的商品分类',
            'brand_id.required'          => '请选择品牌',
            'goods_price.required'       => '请填写商品价格',
            'goods_marketprice.required' => '请填写市场价',
//            'goods_cost.required'        => '请填写成本价',
//            'goods_serial.required'      => '请填写商品编码',
            'goods_stock.required'       => '请填写商品库存',
//            'goods_body.required'        => '请填写商品描述',
            'goods_image.required'       => '请上传商品主图',
            'image_list.required'        => '请上传商品图册',
        ];
    }

}
