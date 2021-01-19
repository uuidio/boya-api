<?php
/**
 * @Filename        CouponRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CouponRequest extends FormRequest
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
            'name'              => 'required',
            'type'              => 'required',
            'scenes'            => 'required',
//                'issue_num'         => 'required_if:scenes,2',
//                'discount'          => 'required_if:type,2',
//                'denominations'     => 'required_if:type,1,3',
            'issue_num'      	=> 'required',
//                'discount'      	=> 'required',
            'denominations'     => 'required',
            'get_star'          => 'required',
            'get_end'           => 'required',
            'start_at'          => 'required',
            'end_at'            => 'required',
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
            'name.required'             => '请输入优惠券名称',
            'issue_num.required'        => '请输入发行数量',
            'scenes.required'           => '请输入使用场景',
            'type.required'             => '请输入优惠券类型',
            'get_star.required'         => '请输入领取开始时间',
            'get_end.required'          => '请输入领取结束时间',
            'start_at.required'         => '请输入生效时间',
            'end_at.required'           => '请输入失效时间',
//                'discount.required'      => '请输入折扣',
            'denominations.required' => '请输入优惠金额',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));

    }
}

