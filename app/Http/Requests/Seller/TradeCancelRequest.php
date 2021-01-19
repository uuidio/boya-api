<?php
/**
 * @Filename TradeCancelRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class TradeCancelRequest extends FormRequest
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
            'tid' => 'required',
            'cancel_reason' => 'required|max:300',
//            'shop_id' => 'required',
            'refund_bn' => '',
            'return_freight' => '',
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
            'tid.required' => '申请售后的订单编号不能为空',
            'cancel_reason.required' => '订单取消原因不能为空',
            'cancel_reason.max' => '订单取消原因不能超过三百个字',
//            'shop_id.required' => '订单所属店铺id',
        ];
    }
}