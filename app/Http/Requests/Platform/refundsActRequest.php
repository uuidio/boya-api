<?php
/**
 * @Filename refundsActRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;


class refundsActRequest extends FormRequest
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
            'aftersales_bn' => '',
            'refunds_id' => 'required',
            'tid' => 'required',
            'oid' => '',
            'shop_id' => 'required',
            'user_id' => 'required',
            'money' => 'required',
            'total_price' => 'required',
            'refund_type' => 'required',
            'refund_bank' => 'required_if:refund_type,offline',
            'receive_bank' => 'required_if:refund_type,offline',
            'refund_account' => 'required_if:refund_type,offline',
            'receive_account' => 'required_if:refund_type,offline',
            'refund_people' => 'required_if:refund_type,offline',
            'beneficiary' => 'required_if:refund_type,offline',
            'payment_id' => 'required',
        ];
    }

    /**
     * Get rule messages.
     * @author hfh
     * @return array
     */
    public function messages()
    {
        return [
            'refunds_id.required' => '退款id必填',
            'shop_id.required' => '店铺id必填',
            'user_id.required' => '会员id必填',
            'money.required' => '实需退款金额必填',
            'total_price.required' => '商家退款金额必填',
            'refund_type.required' => '退款方式必填',
            'refund_bank.required' => '退款银行必填',
            'receive_bank.required' => '收款银行必填',
            'refund_account.required' => '退款帐号必填',
            'receive_account.required' => '收款帐号必填',
            'refund_people.required' => '退款人必填',
            'beneficiary.required' => '收款人必填',
            'payment_id.required' => '支付单必填',
        ];
    }

}
