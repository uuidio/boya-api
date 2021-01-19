<?php
/**
 * @Filename LogisticsTemplatesRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class LogisticsTemplatesRequest extends FormRequest
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
            'name'=>'required',
            'id'=>'',
            'order_sort'=>'',
            'status'=>'',
            'valuation'=> ['required', Rule::in(['1','2','3','4'])],
            'protect'=>'',
            'protect_rate'=>'',
            'minprice'=>'',
            'fee_conf'=>'',
            'free_conf'=>'',
            'is_free'=>'',
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
            'name.required' => '模板名称不能为空',
            'valuation.in' => '运费计算类型,1-按重量,2-按件数,3-按金额,4-按体积',
        ];
    }
}