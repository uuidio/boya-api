<?php
/**
 * @Filename        ExamineRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */


namespace ShopEM\Http\Requests\Platform;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ExamineRequest extends FormRequest
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
            'id' => 'required',
//            'status' => 'required',
            'status' => ['required',Rule::in(['VERIFIED','DENIED'])],//审核通过 or  驳回
        ];
    }

    /**
     * Get rule messages.
     * @author moocde <mo@mocode.cn>
     * @return array
     */
    public function messages()
    {
        return [
            'id.required' => '请输入提现记录id',
//            'status.required' => '请输入审核状态',
            'status.in' => '请输入提现状态,通过-VERIFIED,驳回-DENIED',
            ];
    }
}
