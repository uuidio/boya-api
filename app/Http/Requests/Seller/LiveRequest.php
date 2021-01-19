<?php
/**
 * @Filename        LiveRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class LiveRequest extends FormRequest
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
//                'login_account' => 'required',
//                'password' => 'required|min:6|max:18',
//                'live_id' => 'required',
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
            'login_account.required'     => '请填写用户名',
            'password.required' => '请填写用户密码',
            'password.min'      => '密码不能少于6位',
            'password.max'      => '密码不能大于18位',
        ];
    }

}
