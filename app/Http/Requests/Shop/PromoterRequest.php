<?php
/**
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-02-27
 * @Time: 18:04
 */


namespace ShopEM\Http\Requests\Shop;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class PromoterRequest  extends FormRequest
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
//            'real_name' => 'required',
//            'job_number' => 'required',
//            'mobile' => 'required|cn_phone',
//            'department' => 'required',
            //'id_positive' => 'required',
            //'id_other_side' => 'required',

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
//            'real_name.required' => '真实姓名不能为空',
//            'job_number.required' => '工号不能为空',
//            'mobile.required' => '手机号不能为空',
//            'mobile.cn_phone' => '手机号码格式错误',
//            'department.required' => '部门不能为空',
            //'id_positive.required' => '身份证正面不能为空',
            //'id_other_side.required' => '身份证反面不能为空',
        ];
    }
}
