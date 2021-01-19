<?php
/**
 * @Filename AfterSaleCommitApplyRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Shop;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AfterSaleCommitApplyRequest  extends FormRequest
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
            'oid' => 'required',
            'reason' => 'required',
            'description' => 'max:300',
            'evidence_pic' => '',//照片凭证
            'aftersales_type' => ['required', Rule::in(['ONLY_REFUND','REFUND_GOODS','EXCHANGING_GOODS'])],//售后服务类型,售后服务类型(ONLY_REFUND:只退款，REFUND_GOODS:退货退款，EXCHANGING_GOODS:换货) 默认为只退款
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
            'oid.required' => '申请售后的子订单编号不能为空',
            'reason.required' => '申请售后原因不能为空',
            'description.max' => '申请售后详细说明|描述不能大于300',
            'aftersales_type.in' => '售后服务类型,ONLY_REFUND,REFUND_GOODS,EXCHANGING_GOODS',
        ];
    }
}