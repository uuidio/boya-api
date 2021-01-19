<?php
/**
 * @Filename TradeCancelShopreplyRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;
use Illuminate\Foundation\Http\FormRequest;

class TradeCancelShopreplyRequest extends FormRequest
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
            'cancel_id' => 'required',//取消订单表id
            'status' => 'required',//审核状态 agree 通过，reject 拒绝
            'reason' => '',//仅在审核不通过时填写该值,审核不通过原因
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
            'cancel_id.required' => '取消订单ID不能为空',
            'status.required' => '审核状态 agree 通过，reject 拒绝',
        ];
    }
}