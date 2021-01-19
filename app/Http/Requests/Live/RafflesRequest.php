<?php
/**
 * @Filename        RafflesRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Requests\Live;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RafflesRequest extends FormRequest
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
            'title' => 'required',
            'prize' => 'required',
            'number' => 'required',
            'response' => 'required',
            'response' => 'numeric|min:20',
            'number' => 'numeric|min:1',
        ];
    }

    /**
     * Get rule messages.
     *
     * @author linzhe
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => '请输入直播间标题',
            'prize.required' => '请输入直播间简介',
            'number.required' => '请选择开播时间',
            'response.required' => '请选择商品!',
            'response.min'=>'响应时间不能小于20秒',
            'number.min' => '中奖人数不能小于0',
        ];
    }

    protected function failedValidation(Validator $validator) {
        $error= $validator->errors()->all();
        throw new HttpResponseException(response()->json(['ecode'=>'414','message'=>$error[0],'result'=>[]], 200));

    }
}
