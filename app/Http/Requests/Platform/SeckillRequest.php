<?php
/**
 * @Filename        SeckillRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class SeckillRequest extends FormRequest
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
            'activity_name' => 'required',
            'apply_begin_time' => 'required',
            'apply_end_time' => 'required',
//            'release_time' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
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
            'activity_name.required' => '申请活动名称不能为空!',
            'apply_begin_time.required' => '申请活动开始时间不能为空!',
            'apply_end_time.required' => '申请活动结束时间不能为空!',
//            'release_time' => '商品名称不能为空!',
            'start_time.required' => '活动生效开始时间不能为空!',
            'end_time.required' => '活动生效结束时间不能为空!',
        ];
    }
}
