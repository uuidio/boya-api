<?php

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class LotteryReleaseRequest extends FormRequest
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
            'name' => 'required',
            'status' => 'required',
            'use_type' => 'required',
            'luck_draw_num' => 'required',
            'valid_start_at' => 'required',
            'valid_end_at' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '请填写活动名称',
            'status.required' => '请填写活动状态',
            'use_type.required' => '请填写活动类型',
            'luck_draw_num.required' => '请填写活动可参与次数',
            'valid_start_at.required' => '请输入活动开启时间',
            'valid_end_at.required' => '请输入活动结束时间',
        ];
    }
}
