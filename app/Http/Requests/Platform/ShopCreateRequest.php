<?php
/**
 * @Filename        ShopCreateRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class ShopCreateRequest extends FormRequest
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
            'shop_name'   => 'required',
            'seller_name' => 'required',
            'shop_type' => 'required',
//            'store_code' => 'required',
//            'erp_storeCode' => 'required',
//            'erp_posCode' => 'required',
        ];
    }

    /**
     * Get rule messages.
     * @author moocde <mo@mocode.cn>
     * @return array
     */
    public function messages()
    {
        return [
            'shop_name.required'   => '请输入店铺名称',
            'seller_name.required' => '请选择商家账号',
            'shop_type.required' => '请选择店铺类型',
//            'store_code.required' => '请输入店铺编码',
//            'erp_storeCode.required' => '请输入店铺ID',
//            'erp_posCode.required' => '请输入POS编码',
        ];
    }
}
