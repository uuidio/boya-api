<?php
/**
 * @Filename DeliveryTradeRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class DeliveryTradeRequest extends FormRequest
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
            'tid'        => 'required',
            'corp_code'        => 'required',
            'logi_no'        => 'required',
            'ziti_memo'        => '',
//            'shop_id'        => 'required',
//            'seller_id'        => 'required',
            'memo'        => '',

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
            'tid.required'        => '请填写订单号',
            'corp_code.required'        => '请填写物流公司编号',
            'logi_no.required'        => '请填写快递单号',
//            'shop_id.required'        => '请填写店铺id',
//            'seller_id.required'        => '请填写商家操作员seller_id',
        ];
    }


}