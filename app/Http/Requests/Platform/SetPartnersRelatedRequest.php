<?php
/**
 * @Filename        SetPartnersRelatedRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class SetPartnersRelatedRequest extends FormRequest
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
            'user_id'              => 'required',
            'partner_ids'              => 'required',
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
            'user_id.required'     => '会员id必填',
            'partner_ids.required' => '关联的id必填',
        ];
    }
}
