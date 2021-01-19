<?php
/**
 * @Filename ActivitiesTransmitActRequest.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Http\Requests\Shop;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ActivitiesTransmitCreateTradeRequest  extends FormRequest
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
//            'addr_id' => 'required',
            'rewards_send_id' => 'required',
//            'activities_id' => 'required',
//            'type' => 'required',
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
//            'addr_id.required' => '收货地址id不能为空',
            'rewards_send_id.required' => '奖品id不能为空',
//            'activities_id.required' => '活动id不能为空',
//            'type.required' => '活动类型不能为空',
        ];
    }
}
