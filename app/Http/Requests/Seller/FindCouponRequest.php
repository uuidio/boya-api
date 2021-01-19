<?php
/**
 * @Filename        FindCouponRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class FindCouponRequest extends FormRequest
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
            'bn'      		=> 'required',
            'user_mobile'      	=> 'required',
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
            'bn.required'     		=> '请输入核销码',
            'user_mobile.required'		=> '请输入客户手机号',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));
    }
}
