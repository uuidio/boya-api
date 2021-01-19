<?php
/**
 * @Filename TradeSendBackRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class TradeSendBackRequest extends FormRequest
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
            'aftersales_bn' => 'required',
            'corp_code' => 'required',
            'logi_name' => '',
            'logi_no' => 'required',
            'receiver_address' => '',
            'mobile' => '',
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
            'aftersales_bn.required' => '申请售后的订单编号不能为空',
            'corp_code.required' => '物流公司代码不能为空',
            'logi_no.required' => '物流单号不能为空',
        ];
    }
}