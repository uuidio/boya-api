<?php
/**
 * @Filename        MemberActivityRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl
 */


namespace ShopEM\Http\Requests\Platform;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MemberActivityRequest extends FormRequest
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
            'title' => 'required',
            'listorder' => 'numeric|max:255',
            'is_show' => [Rule::in([0,1])],
            'activity_url' => 'required',
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
            'title.required' => '请输入活动标题',
            'listorder.numeric' => '排序必须为数字!',
            'listorder.max' => '排序不能大于255!',
            'is_show.in' => '状态值错误',
            'activity_url.required' => '请上传活动主图',
        ];
    }
}
