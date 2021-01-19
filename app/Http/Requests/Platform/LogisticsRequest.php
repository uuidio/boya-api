<?php
/**
 * @Filename LogisticsRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class LogisticsRequest extends FormRequest
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
            'id' => '',
            'corp_code' => 'required',
            'full_name' => '',
            'corp_name' => 'required',
            'website' => '',
            'request_url' => '',
            'order_sort' => '',
        ];
    }

    /**
     * Get rule messages.
     * @author hfh
     * @return array
     */
    public function messages()
    {
        return [
            'corp_code.required' => '物流公司代码必填',
            'corp_name.required' => '物流公司简称必填',
        ];
    }

}