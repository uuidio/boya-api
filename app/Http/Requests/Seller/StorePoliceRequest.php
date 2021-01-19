<?php
/**
 * @Filename        StorePoliceRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */


namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class StorePoliceRequest extends FormRequest
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
            'policevalue' => 'integer|min:0|max:99999',
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
            //'policevalue.required' => '库存预警值必填!',
            'policevalue.integer' => '库存预警值必须为整数!',
            'policevalue.min:0' => '库存预警值最小为0!',
            'policevalue.max:99999' => '库存预警值最大为99999!',
        ];
    }
}
