<?php
/**
 * @Filename        SetGoodsRelatedRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class SetPayPasswordRequest extends FormRequest
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
            'password' => 'required|min:6|max:6|confirmed',
            'password_confirmation' => 'required',
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
            'password.required' => '支付密码不能为空',
            'password.min' => '支付密码长度不能小于6位!',
            'password.max' => '支付密码长度不能大于6位!',
            'password.confirmed' => '两次输入的支付密码不一致!',
            'password_confirmation.required' => '确认支付密码不能为空',
        ];
    }
}
