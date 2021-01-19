<?php
/**
 * @Filename        AddPlatformRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class AddPlatformRequest extends FormRequest
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
            'admin_id' => 'required',
            'platform_name' => 'required',
            'app_id' => 'required',
            'secret' => 'required',
            'app_url' => 'required',
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
            'admin_id.required' => '平台账号id',
            'platform_name.required' => '请输入平台项目名称',
            'app_id.required' => '请输入平台项目微信小程序app_id',
            'secret.required' => '请输入平台项目微信小程序secret',
            'app_url.required' => '请输入平台项目接口地址',
        ];
    }

}
