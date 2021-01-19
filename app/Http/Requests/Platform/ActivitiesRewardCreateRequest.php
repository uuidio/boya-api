<?php
/**
 * @Filename        ActivitiesRewardCreateRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */


namespace ShopEM\Http\Requests\Platform;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ActivitiesRewardCreateRequest extends FormRequest
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
            'activities_reward_goods_id' => 'required',
            'type'                       => 'required',
            'goods_stock'                => 'required',
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
            'activities_reward_goods_id.required' => '请输入活动id',
            'type.required'                       => '请输入活动类型',
            'goods_stock.required'                => '请输入库存',
        ];
    }
}
