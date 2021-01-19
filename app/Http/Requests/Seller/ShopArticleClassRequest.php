<?php
/**
 * @Filename        ShopArticleClassRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */


namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class ShopArticleClassRequest extends FormRequest
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
            'name' => 'required',
            'listorder' => 'numeric|max:255',
            'parent_id' => 'numeric',
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
            'gc_name.required' => '请输入分类名称',
            'listorder.numeric' => '排序必须为数字!',
            'listorder.max' => '排序不能大于255!',
            'parent_id.numeric' => '父节点ID错误!',
        ];
    }
}
