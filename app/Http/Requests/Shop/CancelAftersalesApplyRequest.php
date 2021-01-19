<?php
/**
 * @Filename
 *
 * @Copyright    Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License    Licensed <http://www.shopem.cn/licenses/>
 * @authors    Mssjxzw (mssjxzw@163.com)
 * @date        2019-04-29 17:44:56
 * @version    V1.0
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CancelAftersalesApplyRequest extends FormRequest
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
            'tid'             => 'required',
            'oid'             => 'required',
            'aftersales_type' => ['required', Rule::in(['ONLY_REFUND', 'REFUND_GOODS', 'EXCHANGING_GOODS'])],
            //售后服务类型,售后服务类型(ONLY_REFUND:只退款，REFUND_GOODS:退货退款，EXCHANGING_GOODS:换货) 默认为只退款
        ];
    }

    /**
     * Get rule messages.
     *
     * @author hfh
     * @return array
     */
    public function messages()
    {
        return [
            'tid.required'       => '请输入订单号',
            'oid.required'       => '请输入子订单号',
            'aftersales_type.in' => '售后服务类型,ONLY_REFUND,REFUND_GOODS,EXCHANGING_GOODS',
        ];
    }
}