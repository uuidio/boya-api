<?php
/**
 * @Filename AfterSaleOrderStatusRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Shop;
use Illuminate\Foundation\Http\FormRequest;

class AfterRefundRequest extends FormRequest
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
            'shop_id' => 'required',
            'aftersales_bn' => '',
            'refunds_type' => 'required',//退款申请的类型,aftersalse 售后申请退款, cancel 取消订单退款
            'reason' => 'required',
            'total_fee' => '',
            'status' => 'required',//0 未审核, 1 已完成退款,2 已驳回,3 商家审核通过, 4 商家审核不通过, 5 商家强制关单, 6 平台强制关单
            'refund_bn' => '',//退款申请单编号，如果未填写则自动生成
            'return_freight' => '',//"true":退运费,"false":不退运费
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
            'shop_id.required' => '店铺id不能为空',
            'refunds_type.required' => '退款申请的类型不能为空',
            'reason.required' => '退款申请理由不能为空',
            'status.required' => '审核状态不能为空',
        ];
    }
}