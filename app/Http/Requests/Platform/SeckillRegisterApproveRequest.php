<?php
/**
 * @Filename        SeckillRegisterApproveRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class SeckillRegisterApproveRequest extends FormRequest
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
            'registers_id' => 'required',
            'shop_id'      => 'required',
            'status'       => 'required',
        ];
    }

    /**
     * Get rule messages.
     *
     * @author hfh
     * @return array
     */
    public function messages()
    {
        return [
            'registers_id.required' => '活动申请id不能为空!',
            'shop_id.required'      => '店铺id不能为空!',
            'status.required'       => '状态不能为空!',
        ];
    }
}
