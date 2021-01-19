<?php
/**
 * @Filename        UserController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\UserAccount;
use ShopEM\Models\IntegralBySelf;
use ShopEM\Models\UserRelYitianInfo;
use ShopEM\Repositories\IntegralBySelfRepository;
//use ShopEM\Http\Requests\Platform\IntegralBySelfRequest;
use Illuminate\Support\Facades\Cache;
use ShopEM\Services\YitianGroupServices;


class IntegralBySelfController extends BaseController
{
    /**
     * 展示列表
     *
     * @Author Huiho
     * @param Request $request
     * @param IntegralBySelfRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request , IntegralBySelfRepository $repository)
    {
        $input_data = $request->all();
        $input_data['user_id'] = $this->user->id;
        if (!isset($input_data['gm_id']))
        {
            $input_data['gm_id'] = $this->GMID;
        };
        
        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');

        $lists = $repository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);

    }

    /**
     * 资料上传
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {

        $input_data = $request->only('uploaded_data','gm_id' );

        // if (!isset($input_data['gm_id']))
        // {
        //     $input_data['gm_id'] = $this->GMID;
        // };

        if (!isset($input_data['gm_id']) || $input_data['gm_id'] == 3)
        {
            return $this->resFailed(414,'参数丢失，请重新进入');
        }
        $user_info = UserAccount::find($this->user->id);
        $yitianFilter = [
            'user_id' => $this->user->id,
            'gm_id'   => $input_data['gm_id'],
        ];
        $yitian_user = UserRelYitianInfo::where($yitianFilter)->first();
        if (!$yitian_user) 
        {
            return $this->resFailed(414,'请从小程序首页进入该功能');
        }
        $card_type_code = $yitian_user->card_type_code;
        if (empty($card_type_code)) 
        {
            //没有就再获取一次
            $yitanService = new YitianGroupServices($input_data['gm_id']);
            $yitanService->updateCardTypeCode( $this->user->id, $this->user->mobile);
            $card_type_code = UserRelYitianInfo::where($yitianFilter)->value('card_type_code');
            if (empty($card_type_code)) {
                return $this->resFailed(414,'会员信息录入中，请稍后使用~');
            }
        }
        DB::beginTransaction();
        try
        {
            $insert_data = $input_data['uploaded_data'];
            if(!is_array($insert_data))
            {
                throw new \Exception('上传数据有误');
            }
            foreach ($insert_data as $key => $value) {
                if (empty($value)) {
                    throw new \Exception('上传数据有误');
                }
            }
            foreach ($insert_data as $key => $value )
            {
                $create_data['uploaded_data'] = $value;
                $create_data['user_id'] = $this->user->id;
                $create_data['login_account'] = $user_info->login_account;
                //获取crm的会员等级,后面处理
                $create_data['grade_name'] = $card_type_code;
                $create_data['mobile'] = $user_info->mobile;
                $create_data['gm_id'] = $input_data['gm_id'];

                IntegralBySelf::create($create_data);
            }
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess([], '提交成功!');

    }

    /**
     * 结果详情
     *
     * @Author Huiho
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = IntegralBySelf::find($id);

        if (empty($detail))
        {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);

    }



}
