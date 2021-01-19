<?php
/**
 * @Filename        AddRateAppendRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class AddRateAppendRequest extends FormRequest
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
            'rate_id' => 'numeric|required',
            'content' => 'required|min:5|max:300',
        ];
    }

    /**
     * Get rule messages.
     *
     * @author djw
     * @return array
     */
    public function messages()
    {
        return [
            'rate_id' => '评价ID不能为空！',
            'rate_id.numeric' => '必须为数字！',
            'content' => '回复内容必填|回复内容最少5个字|回复内容最多300个字',
            'content.min' => '回复内容最少5个字',
            'content.max' => '回复内容最多300个字',
        ];
    }
}
