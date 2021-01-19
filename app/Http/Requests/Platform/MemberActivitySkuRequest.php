<?php
/**
 * @Filename        MemberActivityRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl
 */


namespace ShopEM\Http\Requests\Platform;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class MemberActivitySkuRequest extends FormRequest
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
            'apply_start_time' => 'required',
            'apply_end_time' => 'required',
            'activity_start_time' => 'required',
            'activity_end_time' => 'required',
            'apply_way' => [Rule::in([1,5])],
            'apply_way' => 'required',
            'place' => 'required',

        ];
    }

    /**
     * Get rule messages.
     * @author swl
     * @return array
     */
    public function messages()
    {
        return [
            'apply_start_time.required' => '报名开始时间不能为空',
            'apply_end_time.required' => '报名结束时间不能为空',
            'activity_start_time.required' => '活动开始时间不能为空',
            'activity_end_time.required' => '活动结束时间不能为空',
            'apply_way.in' => '报名方式数字错误',  
            'apply_way.required' => '报名方式不能为空',     
            'place.required' => '活动场地不能为空',      
        ];
    }
}
