<?php
/**
 * @Filename        WeChatMiniOpenIdRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class WeChatMiniOpenIdRequest extends FormRequest
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
            'code' => 'required',
            'encryptedData' => 'required',
            'iv' => 'required',
//            'mobile' => 'required',
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
            'code.required' => '缺少code参数',
            'encryptedData.required' => '缺少encryptedData参数',
            'iv.required' => '缺少iv参数',
//            'mobile.required' => '缺少手机号码',
        ];
    }
}
