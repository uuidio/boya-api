<?php
/**
 * @Filename        AddRateRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class AddRateRequest extends FormRequest
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
            'tid'                   => 'numeric|required',
            'tally_score'           => 'numeric|required|between:1,5',
            'attitude_score'        => 'numeric|required|between:1,5',
            'delivery_speed_score'  => 'numeric|required|between:1,5',
            'rate_data'             => 'required',
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
            'tid.numeric'                   => '必须为数字',
            'tid.required'                  => '订单号不能为空',
            'tally_score.numeric'           => '描述相符评分必须为数字',
            'tally_score.required'          => '描述相符评分必填',
            'tally_score.between'           => '评分范围1-5',
            'attitude_score.numeric'        => '服务态度评分必须为数字',
            'attitude_score.required'       => '服务态度评分必填',
            'attitude_score.between'        => '评分范围1-5',
            'delivery_speed_score.numeric'  => '发货速度评分必须为数字',
            'delivery_speed_score.required' => '发货速度评分必填',
            'delivery_speed_score.between'  => '评分范围1-5',
            'rate_data.required'            => '商品评价不能为空',
        ];
    }
}
