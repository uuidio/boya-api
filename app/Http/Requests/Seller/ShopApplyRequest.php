<?php
/**
 * @Filename ShopApplyRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ShopApplyRequest extends FormRequest
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
            'shop_name'=>'required',
            'shop_type' => ['required', Rule::in(['flag','brand','cat','self','store'])],
            'company_name' => 'required',
            'license_num' => '',
            'license_img' => '',
            'representative' => 'required',
            'corporate_identity' => 'required',
            'license_area' => '',
            'license_addr' => '',
            'establish_date' => '',
            'license_indate' => '',
            'enroll_capital' => '',
            'scope' => '',
            'shop_url' => '',
            'company_area' => '',
            'company_addr' => '',
            'company_phone' => '',
            'company_contacts' => '',
            'company_cmobile' => '',
            'tissue_code' => '',
            'tissue_code_img' => '',
            'tax_code' => '',
            'tax_code_img' => '',
            'bank_user_name' => '',
            'bank_name' => '',
            'cnaps_code' => '',
            'bankID' => '',
            'bank_area' => '',
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
            'shop_name.required' => '店铺名称不能为空',
            'shop_type.in' => '申请店铺类型,flag,brand,cat,self,store',
            'company_name.required' => '公司名称不能为空',
            'representative.required' => '法定代表人姓名不能为空',
            'corporate_identity.required' => '法人身份证号不能为空!',
        ];
    }
}