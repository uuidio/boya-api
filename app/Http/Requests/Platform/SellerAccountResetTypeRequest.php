<?php
/**
 * @Filename        SellerAccountResetTypeRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          zhh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class SellerAccountResetTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to SellerAccountResetTypeRequest.phpmake this request.
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
            'id'    => 'required',
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
            'id.required' => '商家id不能为空',
        ];
    }
}
