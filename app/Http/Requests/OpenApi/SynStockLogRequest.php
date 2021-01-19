<?php

namespace ShopEM\Http\Requests\OpenApi;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SynStockLogRequest extends FormRequest
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
            'tid' => 'required',
            'status' => 'required',
            'reason' => 'required_if:status,2',
        ];
    }

    public function messages()
    {
        return [
            'gm_id.required'        => '请输入项目编号',
            'tid.required'          => '请输入订单编号',
            'status.required'       => '请输入状态',
            'reason.required_if'    => '请输入失败原因',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));

    }
}
