<?php
/**
 * @Filename        SellerAccountRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Requests\Seller;

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
        $id = $this->get('id');
        if ($id > 0) {
            $rules = ['username' => 'required'];

            if (!empty($this->get('password'))) {
                $rules['password'] = 'required|min:6|max:18';
            }

            return $rules;
        } else {
            return [
                'username' => 'required',
                'password' => 'required|min:6|max:18',
            ];
        }
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
            'name.required'     => '请填写用户名',
            'password.required' => '请填写用户密码',
            'password.min'      => '密码不能少于6位',
            'password.max'      => '密码不能大于18位',
        ];
    }

}
