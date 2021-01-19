<?php
/**
 * @Filename        SetPartnersRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */


namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class SetPartnersRequest extends FormRequest
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
            'id' => 'required',
            'partner_status' => 'required',
            'partner_role' => 'required',
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
            'id.required' => '请输入会员id',
            'partner_status.required' => '请输入状态',
            'partner_role.required' => '请输入身份类型',
        ];
    }
}
