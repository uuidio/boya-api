<?php
/**
 * @Filename UserCardController.php
 * 会员卡
 * @Author swl 2020-4-2
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\YiTianUserCard;
use ShopEM\Models\UserRelYitianInfo;
use Illuminate\Http\Request;
use ShopEM\Repositories\UserCardRepository;

class UserCardController extends BaseController
{

     /**
     * 会员卡列表
     *
     * @Author swl
     * @param UserCardRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,UserCardRepository $repository)
    {
        $data = $request->all();
        $data['is_self'] = 0;
        // 判断是查看所有平台的会员卡还是单个平台的所有会员卡
        if(!empty($data['gm_id'])){
            $lists = $repository->listItems($data);
        }else{
            $lists = $repository->shopListItems($data);
        }

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        foreach ($lists as $key => $value) {
            $user_default = -1;
            if (checkUserAuthStatus('shop_users')) {
                $user_id = $this->user->id;
                $default = UserRelYitianInfo::where(['user_id'=>$user_id,'default'=>1])->value('gm_id');
                $user_default = empty($default) ? 0 : $default;
            }
            $lists[$key]['user_default'] = $user_default;
        }
        return $this->resSuccess($lists);
    }
}