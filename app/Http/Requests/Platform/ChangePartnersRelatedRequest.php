<?php
/**
 * @Filename        ChangePartnersRelatedRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class ChangePartnersRelatedRequest extends FormRequest
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
            'user_id'    => 'required',
            'partner_id' => 'required',
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
            'user_id.required'    => '会员id必填',
            'partner_id.required' => '关联的合伙人id必填',
        ];
    }
}
