<?php
/**
 * @Filename        AddUserAddressRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */


namespace ShopEM\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class AddUserAddressRequest extends FormRequest
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
//            'housing_id'   => 'required',
//            'housing_name' => 'required',
            'name'         => 'required',
            'tel'          => 'required|cn_phone',
            'address'     => 'required',
            'area_code'    => 'required',
            'province'    => 'required',
            'city'    => 'required',
            'county'    => 'required',
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
//            'housing_id.required'   => '请选择小区',
//            'housing_name.required' => '请选择小区',
            'name.required'         => '请填写收货人',
            'tel.required'          => '请填写收货电话',
            'tel.cn_phone' => '手机号码格式错误',
            'address.required'     => '请填写地址',
            'area_code.required'    => '请选择所在地区',
            'province.required'    => '请选择所在省份',
            'city.required'    => '请选择所在城市',
            'county.required'    => '请选择所在区县',
        ];
    }
}
