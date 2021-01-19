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

namespace ShopEM\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class CheckMobileCodeRequest extends FormRequest
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
            'mobile' => 'required|cn_phone',
            'code' => 'required',
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
            'code.required' => '请输入验证码',
            'mobile.required' => '请输入手机号码',
            'mobile.cn_phone' => '手机号码格式错误',
        ];
    }
}
