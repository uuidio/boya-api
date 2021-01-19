<?php
/**
 * @Filename        LiveRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Requests\Live;

use Illuminate\Foundation\Http\FormRequest;

class ForeshowRequest extends FormRequest
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
            'title' => 'required',
            'introduce' => 'required',
            'start_at' => 'required',
            'goodsids' => 'required',
            'img_url' => 'required'
        ];
    }

    /**
     * Get rule messages.
     *
     * @author linzhe
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => '请输入直播间标题',
            'introduce.required' => '请输入直播间简介',
            'start_at.required' => '请选择开播时间',
            'goodsids.required' => '请选择商品!',
            'img_url.required' => '请上传封面图'
        ];
    }
}
