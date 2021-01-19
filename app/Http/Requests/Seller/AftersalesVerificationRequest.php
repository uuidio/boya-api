<?php
/**
 * @Filename AfterSalesVerificationRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class AfterSalesVerificationRequest extends FormRequest
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
            'aftersales_bn'        => 'required',
//            'shop_id'        => 'required',
            'check_result'        => 'required',
            'aftersales_type'        => 'required',  //   'ONLY_REFUND' => '仅退款','REFUND_GOODS' => '退货退款','EXCHANGING_GOODS' => '换货',
            'shop_explanation'        => 'max:200',
            'total_price'        => 'numeric|nullable',
            'refunds_reason'        => 'max:200',
        ];
    }

    /**
     * Get rule messages.
     *
     * @author moocde <mo@mocode.cn>
     * @return array
     */
    public function messages()
    {
        return [
            'aftersales_bn.required'        => '申请售后的编号必填',
//            'shop_id.required'        => '售后单所属店铺的店铺id必填',
            'check_result.required'        => '审核结果,同意或不同意,(true,false)',
            'aftersales_type.required'        => '售后类型必填',
            'shop_explanation.max'        => '商家审核处理说明必须小于200个字',
            'total_price.numeric'        => '退款金额请输入数字',
            'refunds_reason.max'        => '退款申请原因|退款申请原因必须小于200字',
        ];
    }

}
