<?php

/**
 * @Author: nlx
 * @Date:   2020-03-16 15:32:26
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-03-16 15:38:30
 */
namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;


class ClassName extends FormRequest
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
            'roomid' => 'required',
            'name' => 'required',
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
            'roomid.required' => '请填写房间id',
            'name.required' => '请填写房间名',
        ];
    }
}