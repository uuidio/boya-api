<?php
/**
 * @Filename        HousingRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class HousingRequest extends FormRequest
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
            'housing_name' => 'required',
            'province_id'  => 'required',
            'city_id'      => 'required',
            'area_id'      => 'required',
            'street_id'    => 'required',
            'lng'          => 'required',
            'lat'          => 'required',
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
            'housing_name.required' => '请输入住宅小区名称',
            'province_id.required'  => '请输入小区所在省份ID',
            'city_id.required'      => '请输入小区所在城市ID',
            'area_id.required'      => '请输入小区所在区县ID',
            'street_id.required'    => '请输入小区所在街道ID',
            'shop_name.required'    => '请输入店铺名称',
            'lng.required'          => '请输入经度',
            'lat.required'          => '请输入纬度',
        ];
    }
}
