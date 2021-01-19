<?php
/**
 * @Filename        ChangeUserPointRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class ChangeUserPointRequest extends FormRequest
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
            'user_id' => 'required|numeric',
            'modify_point' => 'required|numeric|max:999999999',
            'modify_remark' => '',
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
            'user_id.required' => '会员ID不能为空',
            'numeric.numeric' => '会员ID必须为数字',
            'modify_point.required' => '积分值不能为空',
            'modify_point.numeric' => '积分值必须为数字',
            'modify_point.max' => '积分值长度过长',
        ];
    }
}
