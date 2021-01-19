<?php
/**
 * @Filename        SignupUserRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SignupUserRequest extends FormRequest
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
            'username' => 'required',
            'password' => 'required|min:6|max:20|confirmed',
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
            'username.required' => '账号不能为空',
            'password.required' => '密码不能为空',
            'password.min' => '密码长度不能小于6位!',
            'password.max' => '密码长度不能大于20位!',
            'password.confirmed' => '两次输入的密码不一致!',
            'password_confirmation.required' => '确认密码不能为空',
        ];
    }
}
