<?php
/**
 * @Filename        BuildTradeRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Requests\Shop;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class BuildTradeRequest extends FormRequest
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
            // 'addr_id' => '',
            'pay_type' => '',
            'pick_type' => ['required', Rule::in(['0','1','2'])],
            'recharge_info.tel' => 'cn_phone',
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
//            'addr_id.required' => '请选择地址',
            'pick_type.required' => '请选择配送类型',//0是快递,1是自提
            'pick_type.in' => '请选择正确的配送类型',//0是快递,1是自提
            'recharge_info.tel.cn_phone' => '手机号格式不正确',
        ];
    }
}
