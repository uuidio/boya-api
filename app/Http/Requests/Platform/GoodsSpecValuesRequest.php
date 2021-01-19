<?php
/**
 * @Filename GoodsSpecValuesRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;


class GoodsSpecValuesRequest extends FormRequest
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
            'attr_name' => 'required',
            'attr_id' => 'required',
            'type_id' => 'required',
            'attr_sort' => '',
            'attr_value' => '',
            'attr_show' => '',
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
            'attr_name.required' => '请输入属性名称',
            'attr_id.required' => '请输入属性id',
            'type_id.required' => '请输入类型id',
        ];
    }
}