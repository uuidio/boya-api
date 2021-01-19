<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-05-28 10:41:25
 * @version 	V1.0
 */

namespace ShopEM\Http\Requests\Seller;

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
    	$return = [
	            'act_id'      			=> ['required'],
	            'goods_info'      		=> 'required',
	            'goods_info.*.discount' => ['required'],
        ];
        $request = new FormRequest();
        $return['goods_info.*.discount'][] = function($attribute, $value, $fail) use ($request) {
        	$act_id = request('act_id');
			$act = \ShopEM\Models\SpecialActivity::find($act_id);
			if ($act) {
				if (!isInTime($act->star_apply,$act->end_apply)) {
					return $fail('报名未开始或已结束');
				}
				$range = explode('-', $act->range);
				if ($value < $range[0] || $value > $range[1]) {
					$field = explode('.', $attribute);
					$goods_info = request('goods_info');
					return $fail($goods_info[$field[1]]['goods_name'].'超出折扣范围');
				}
			}else{
				return $fail('无此活动');
			}
        };
        $return['goods_info.*.goods_id'][] = function ($attribute, $value, $fail) use ($request)
        {
            $service = new \ShopEM\Services\Marketing\SpecialActivity();
            $check = $service->checkActGoods($value);
            if ($check) {
                $act_name = \ShopEM\Models\SpecialActivity::select('name')->find($check['act_id']);
                return $fail($check['goods']->goods_name.'已经参加了'.$act_name['name'].'活动');
            }
        };
        return $return;
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
	            'act_id.required'     			=> '请选择活动',
	            'goods_info.required'			=> '请输入报名的商品',
	            'goods_info.*.discount.required'=> '请输入报名商品优惠力度',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));
 
    }
}