<?php
/**
 * @Filename        replyRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SeckillRequest extends FormRequest
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
            'good_name' => 'required',
            'goods_id' => 'required',
            'goods_price' => 'required',
            'goods_image' => 'required',
            'seckills_stock' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
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
            'good_name' => '商品名称不能为空!',
            'goods_id' => '商品id不能为空!',
            'goods_price' => '秒杀不能为空!',
            'goods_image' => '秒杀商品图片不能为空!',
            'seckills_stock' => '秒杀商品库存不能为空!',
            'start_time' => '秒杀开始时间不能为空!',
            'end_time' => '秒杀结束时间不能为空!',
        ];
    }
}
