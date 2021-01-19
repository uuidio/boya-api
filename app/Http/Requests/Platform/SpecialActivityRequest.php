<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-06-19 14:16:54
 * @version 	V1.0
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SpecialActivityRequest extends FormRequest
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
                'name'      => 'required',
	            'type'      => 'required',
                'send_goods' => 'required_if:type,3',
	            // 'range'      => 'required',
	            'star_apply' => 'required',
	            'end_apply'  => 'required',
	            'star_time' => 'required',
	            'end_time'  => 'required',
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
                'name.required'     	=> '请输入活动名称',
	            'range.required'		=> '请输入优惠范围',
	            'type.required'     	=> '请输入活动类型',
	            'star_time.required'	=> '请输入生效时间',
	            'end_time.required' 	=> '请输入失效时间',
	            'star_apply.required' 	=> '请输入报名开始时间',
                'end_apply.required'    => '请输入报名结束时间',
	            'send_goods.required_if'  => '请输入赠送的商品',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));

    }
}
