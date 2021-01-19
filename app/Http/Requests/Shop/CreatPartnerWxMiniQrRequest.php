<?php
/**
 * @Filename        CreatPartnerWxMiniQrRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreatPartnerWxMiniQrRequest extends FormRequest
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
            'type' => ['required', Rule::in(['2', '3', '4'])],
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
            'type.required' => '请选择生成合伙人二维码类型类型',//0是快递,1是自提,3是虚拟商品
            'type.in'       => '请选择正确的类型',//0是快递,1是自提,3是虚拟商品
        ];
    }
}
