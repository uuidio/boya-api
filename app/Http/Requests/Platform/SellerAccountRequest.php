<?php
/**
 * @Filename        SellerAccountRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class SellerAccountRequest extends FormRequest
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
            'username'    => 'required|username|unique:seller_accounts',
            'password'    => 'required|min:6|max:20|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    /**
     * Get rule messages.
     * @author hfh
     * @return array
     */
    public function messages()
    {
        return [
            'username.required'    => '请输入商家账号',
            'username.username'    => '用户名不合法',
            'username.unique'      => '用户名已经存在',
            'password.required' => '密码不能为空',
            'password.min' => '密码长度不能小于6位!',
            'password.max' => '密码长度不能大于20位!',
            'password.confirmed' => '两次输入的密码不一致!',
            'password_confirmation.required' => '确认密码不能为空',
        ];
    }
}
