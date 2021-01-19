<?php
/**
 * @Filename        PointActivityRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PointActivityRequest extends FormRequest
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
            'goods_id' => 'required',
            'point_price' => 'required|numeric',
            'point_fee' => 'required|integer|min:1',
            'point_class_id' => 'required',
            'active_start' => 'required',
            'active_end' => 'required',
            'sort' => 'required|numeric',
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
            'goods_id.required' => '商品不能为空!',
            'point_price.required' => '活动价格不能为空!',
            'point_price.numeric' => '活动价格只能为数字!',
            'point_fee.required' => '请设置所需积分!',
            'point_fee.integer' => '所需积分为整数',
            'point_fee.min'  => '所需积分必须大于0',
            'point_class_id.required' => '请选择归属分类!',
            'sort.required' => '排序参数错误!',
            'sort.numeric' => '排序参数只能为数字',
            'active_start.required' => '请选择兑换时间!',
            'active_end.required' => '请选择兑换时间!',
        ];
    }
}
