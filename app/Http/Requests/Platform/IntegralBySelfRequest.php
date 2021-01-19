<?php
/**
 * @Filename        AdminRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class IntegralBySelfRequest extends FormRequest
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
            // 'address' => 'required',
            'invoice_at' => 'required',
            'fee' => 'required',
            'ticket_id' => 'required',
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
            // 'address.required'     => '请输入消费地点',
            'invoice_at.required' => '请输入开票时间',
            'fee.required'      => '请输入消费金额',
            'ticket_id.required'      => '请输入票据号',
        ];
    }
}
