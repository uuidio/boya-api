<?php
/**
 * @Filename GoodsSpecValueRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class GoodsSpecValueRequest extends FormRequest
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
            'sp_value_name' => 'required',
            'sp_id'         => 'required',
            'cat_id'        => 'required',
            'shop_id'       => 'required',
        ];
    }

    /**
     * Get rule messages.
     *
     * @author hfh
     * @return array
     */
    public function messages()
    {
        return [
            'sp_value_name.required' => '规格值名称不为空',
            'sp_id.required'         => '所属规格id不为空',
            'cat_id.required'        => '分类id不为空',
            'shop_id.required'       => '店铺id不为空',
    ];
    }

}