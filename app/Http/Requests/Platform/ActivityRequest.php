<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-06-19 15:26:57
 * @version 	V1.0
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ActivityRequest extends FormRequest
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
                'name'      	=> 'required',
	            'type'      	=> 'required',
	            'rule'      	=> 'required',
	            'star_time' 	=> 'required',
	            'end_time'  	=> 'required',
	            'limit_goods'	=> 'required',
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
	            'rule.required'			=> '请输入活动规则',
	            'type.required'     	=> '请输入活动类型',
	            'star_time.required'	=> '请输入生效时间',
	            'end_time.required' 	=> '请输入失效时间',
	            'limit_goods.required' 	=> '请输入绑定商品',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));

    }
}