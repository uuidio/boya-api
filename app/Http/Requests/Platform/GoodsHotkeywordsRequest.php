<?php
/**
 * @Filename        GoodsHotkeywordsRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */


namespace ShopEM\Http\Requests\Platform;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class GoodsHotkeywordsRequest extends FormRequest
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
            'keyword' => 'required',
            'listorder' => 'numeric|max:999',
            'disabled' => [Rule::in([0,1])],
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
            'keyword.required' => '请输入关键字',
            'listorder.numeric' => '排序必须为数字!',
            'listorder.max' => '排序不能大于999!',
            'disabled.in' => '状态值错误',
        ];
    }
}
