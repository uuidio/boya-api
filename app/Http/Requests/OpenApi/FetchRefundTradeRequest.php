<?php

namespace ShopEM\Http\Requests\OpenApi;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FetchRefundTradeRequest extends FormRequest
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
            'gm_id' => 'required',
            'apply_start' => 'required',
            'apply_end' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'gm_id.required'        => '请输入项目编号',
            'apply_start.required' => '请输入申请时间范围开始',
            'apply_end.required'   => '请输入申请时间范围结束',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));

    }
}
