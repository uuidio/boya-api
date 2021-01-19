<?php
/**
 * @Filename        UnfreezePartnersRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UnfreezePartnersRequest extends FormRequest
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
            'id'           => 'required',
            'partner_type' => 'required',
            'type'         => 'required',
        ];
    }

    /**
     * Get rule messages.
     *
     * @return array
     * @author moocde <mo@mocode.cn>
     */
    public function messages()
    {
        return [
            'id.required'        => '会员id必填',
            'partner_type.array' => '修改身份类型必填',
            'type.required'      => '操作类型必填',
        ];
    }
}
