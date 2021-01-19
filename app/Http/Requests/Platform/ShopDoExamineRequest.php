<?php
/**
 * @Filename ShopDoExamineRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ShopDoExamineRequest extends FormRequest
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
            'shop_id' => 'required',
            'status' => ['required', Rule::in(['active','locked','successful','failing'])],
            'reason' => '',
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
            'shop_id.required' => '请输入要审核的店铺id',
            'status.in' => '请输入正确的审核状态',//active-未审核,locked-审核中,successful-审核通过,failing-审核驳回,finish-开店完成
        ];
    }
}