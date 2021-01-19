<?php
/**
 * @Filename GroupApplySaveRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class GroupApplySaveRequest extends FormRequest
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
            'goods_info'       => 'required',
            'group_size'       => 'required|integer|min:2|max:6',//拼团人数
            'group_validhours' => 'required|integer|min:1',//有效时间
            'start_time'       => 'required',
            'end_time'         => 'required',
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
            'goods_info.required'       => '添加活动的商品信息不能为空',
            'group_size.required'       => '拼团人数不能为空',
            'group_size.min'            => '拼团人总数至少是2人',
            'group_size.max'            => '拼团人总数不能大于6人',
            'group_validhours.required' => '拼团有效期不能为空',
            'group_validhours.integer' => '拼团有效期为整数',
            'group_validhours.min'  => '拼团有效期不能低于1小时',
            'start_time.required'       => '拼团开始时间不能为空',
            'end_time.required'         => '拼团结束时间不能为空',
        ];
    }
}