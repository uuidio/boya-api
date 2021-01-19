<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-04-29 17:42:05
 * @version 	V1.0
 */

namespace ShopEM\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeSelfPointRequest extends FormRequest
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
            'source_point' => 'required|numeric',
            'add_point' => 'required|numeric',
            'current_gm_id' => 'required',
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
        	'add_point.numeric'   => '牛币值必须为数字',
            'add_point.required' => '请输入牛币值',
            'source_point.numeric'   => '积分值必须为数字',
            'source_point.required' => '请输入积分值',
            'current_gm_id.required' => '当前项目id必传',
        ];
    }
}
