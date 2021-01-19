<?php
/**
 * @Filename    GetUserPertnerInfoRequest.php
 *
 * @Copyright    Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License    Licensed <http://www.shopem.cn/licenses/>
 * @authors    hfh
 * @date        2019-06-19 15:26:57
 * @version    V1.0
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetUserPertnerInfoRequest extends FormRequest
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
            'id'   => 'required',
            'type' => 'required',
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
            'id.required'   => '请输入会员id',
            'type.required' => '请输入请输入类型',
        ];
    }

}