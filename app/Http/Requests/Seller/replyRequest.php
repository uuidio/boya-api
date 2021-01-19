<?php
/**
 * @Filename        replyRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class replyRequest extends FormRequest
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
            'reply_content' => 'required|min:5|max:300',
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
            'rate_id.numeric' => '必须为数字！',
            'rate_id.required' => '评价ID不能为空！',
            'reply_content.required' => '回复内容必填',
            'reply_content.min' => '回复内容最少5个字',
            'reply_content.max' => '回复内容最多300个字',
        ];
    }
}
