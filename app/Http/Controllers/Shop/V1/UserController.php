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
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Http\Requests\Shop\AddUserAddressRequest;
use ShopEM\Http\Requests\Shop\ModifyPwdRequest;
use ShopEM\Http\Requests\Shop\ModifyPayPwdRequest;
use ShopEM\Models\ActivityBargains;
use ShopEM\Models\UserAddress;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserDeposit;
use ShopEM\Models\UserShareLog;
use Illuminate\Support\Facades\Cache;
use ShopEM\Jobs\AddEventToCrm;
use ShopEM\Models\WxMiniSubscribeMessages;

class UserController extends BaseController
{
    /**
     * 用户基本信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail()
    {
        $detail = $this->user;
        $profile = \ShopEM\Models\UserProfile::select('birthday','sex','name','username','head_pic')->find($this->user['id']);
        $detail['birthday'] = date('Y-m-d', $profile['birthday']);
        $detail['sex'] = $profile['sex'];
        $sex_text = [
            0 => '女',
            1 => '男',
            2 => '保密'
        ];
        $detail['sex_text'] = $sex_text[$profile['sex']];
        $detail['name'] = $profile['name'];
        $detail['username'] = $profile['username'];
        $detail['head_pic'] = $profile['head_pic'];

        //积分
        $point = \ShopEM\Models\UserPoint::select('point_count')->find($this->user['id']);
        $detail['point'] = isset($point['point_count']) ? $point['point_count'] : 0;

        //预存款
        $deposit = \ShopEM\Models\UserDeposit::select('deposit')->where('user_id', $this->user->id)->first();
        $detail['deposit'] = isset($deposit['deposit']) ? $deposit['deposit'] : 0;
        return $this->resSuccess($detail);
    }

    /**
     * 保存收货地址
     *
     * @Author moocde <mo@mocode.cn>
     * @param AddUserAddressRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeAddress(AddUserAddressRequest $request)
    {
        $data = $request->only('housing_id', 'housing_name', 'name', 'tel', 'province', 'city', 'county', 'address', 'area_code', 'postal_code');
        $data['is_default'] = $request->is_default ? 1 : 0;
        if ($data['is_default'] == 1) {
            UserAddress::where('user_id', $this->user['id'])->update(['is_default' => 0]);
        }
        $data['user_id'] = $this->user['id'];
        $addr = UserAddress::create($data);

        return $this->resSuccess($addr);
    }

    /**
     * [checkPayPassword 检查用户是否设置支付密码]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function checkPayPassword()
    {
        $deposit = UserDeposit::where('user_id',$this->user->id)->first();
        if (!$deposit) {
            return $this->resSuccess(['code'=>1,'msg'=>'未设置支付密码']);
        }else{
            return $this->resSuccess(['code'=>0,'msg'=>'']);
        }
    }

    /**
     * 用户收货地址列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function addressLists()
    {
        $lists = UserAddress::where('user_id', $this->user['id'])->orderBy('id', 'desc')->get();
        $isDefault = UserAddress::select('id')->where('user_id', $this->user['id'])->where('is_default', 1)->first();
        if ($lists) {
            $lists = $lists->toArray();
            foreach ($lists as $key => $address) {
                $lists[$key]['id'] = (string)$lists[$key]['id'];
            }
        }
        $result = [
            'default_id' => isset($isDefault['id']) ? (string)$isDefault['id'] : '',
            'lists' => $lists
        ];
        return $this->resSuccess($result);
    }

    /**
     * 收货地址详情
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailAddress($id)
    {
        $detail = UserAddress::where('user_id', $this->user['id'])->where('id', $id)->orderBy('id', 'desc')->first();
        if (empty($detail)) {
            return $this->resFailed(406);
        }
        return $this->resSuccess($detail);
    }

    /**
     * 用户删除地址
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|int
     */
    public function deleteAddress(Request $request)
    {
        if (!$request->has('addr_id')) {
            return $this->resFailed(406);
        }

        $addr = UserAddress::where('user_id', $this->user['id'])->where('id', $request->addr_id)->first();

        if (empty($addr)) {
            return $this->resFailed(406);
        }

        return $this->resSuccess($addr->delete());
    }

    /**
     * 编辑用户地址
     *
     * @Author moocde <mo@mocode.cn>
     * @param AddUserAddressRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAddress(AddUserAddressRequest $request)
    {
        $addr = UserAddress::where('user_id', $this->user['id'])->where('id', $request->addr_id)->first();

        if (empty($addr)) {
            return $this->resFailed(406);
        }

        $data = $request->only('housing_id', 'housing_name', 'name', 'tel', 'province', 'city', 'county', 'address', 'area_code', 'postal_code');
        $data['is_default'] = $request->is_default ? 1 : 0;
        if ($data['is_default'] == 1) {
            UserAddress::where('user_id', $this->user['id'])->update(['is_default' => 0]);
        }

        return $this->resSuccess($addr->update($data));
    }

    /**
     * 修改密码
     *
     * @Author djw
     * @param ModifyPwdRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function ModifyPwd(ModifyPwdRequest $request)
    {

        $data = $request->all();

        $accountUser_data['password'] = bcrypt($data['password']);

        try {
            UserAccount::where('id', $this->user['id'])->update($accountUser_data);
        } catch (Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 修改支付密码(支付密码)
     *
     * @Author hfh_wind
     * @param modifyPayPwd $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function modifyPayPwd(ModifyPayPwdRequest $request)
    {

        $data = $request->only('password');

        $accountUser_data['password'] = bcrypt($data['password']);

        $user_id = $this->user->id;

        $check_isset = UserDeposit::where('user_id', $user_id)->count();

        try {
            if ($check_isset) {
                UserDeposit::where('user_id', $user_id)->update($accountUser_data);
            } else {
                $accountUser_data['user_id'] = $user_id;
                UserDeposit::create($accountUser_data);
            }

        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess([], '设置成功!');
    }


    /**
     * 修改会员信息
     *
     * @Author djw
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function modifyUserProfiles(Request $request)
    {
        $data = $request->only('name', 'username', 'birthday', 'sex', 'head_pic','email','real_name');

        $user_id = $this->user->id;

        try {
            $res = \ShopEM\Services\User\UserPassport::modifyUserProfiles($user_id, $data);
        } catch (\Exception $e) {
            return $this->resFailed(702, $e->getMessage());
        }

        return $this->resSuccess($res, '设置成功!');
    }

    /**
     * [mobileVerification 验证是否绑定手机号]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function mobileVerification()
    {
        if ($this->user->mobile) {
            return $this->resSuccess(['code'=>1,'msg'=>$this->user->mobile]);
        }else{
            return $this->resSuccess(['code'=>0,'msg'=>'未绑定']);
        }
    }

    /**
     * [bindMobile 绑定手机号]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function bindMobile(Request $request)
    {
        $check = checkInput($request->all(),'checkMobileCode','user');
        if($check['code']){
            return $this->resFailed(414,$check['msg']);
        }
        $check['code'] = 0;
        $check = checkCode('checkMobile',$request->mobile,$request->code);
        if ($check['code']) {
            return $this->resFailed(600, $check['msg']);
        }
        $id = $this->user->id;
        UserAccount::where('id',$id)->update(['mobile'=>$request->mobile]);
        \Illuminate\Support\Facades\Cache::forget('cache_key_user_id_'.$id);
        return $this->resSuccess();
    }

    /**
     * 获取手机验证码
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @param Sms $sms
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendCheckMobileCode(Request $request)
    {
        $check = checkInput($request->all(),'sendMobileCode','user');
        if($check['code']){
            return $this->resFailed(414,$check['msg']);
        }
        $params = [
            'mobile'=>$request->mobile,
            'domain'=>'checkMobile',
        ];
        $send = sendCode('mobile',$params);
        if ($send['code']) {
            return $this->resFailed(600, $send['msg']);
        }
        return $this->resSuccess($send['msg']);
    }

    /**
     * [checkOpenid 检查会员是否绑定openid]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function checkOpenid()
    {
        $obj = UserAccount::find($this->user->id);
        if ($obj->openid) {
            return $this->resSuccess(true);
        }else{
            return $this->resSuccess(false);
        }
    }

    /**
     * [bindOpenid 绑定openid]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function bindOpenid(Request $request)
    {
        if (!$request->filled('openid')) {
            return $this->resFailed(414,'参数不全');
        }
        $obj = UserAccount::find($this->user->id);
        $obj->openid = $request->openid;
        $obj->save();
        return $this->resSuccess();
    }

    /**
     * [message 留言]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function message(Request $request)
    {
        if (!$request->filled('content')) {
            return $this->resFailed(414,'留言内容不能为空');
        }
        $data['user_id'] = $this->user->id;
        $data['content'] = $request->content_text;
        \ShopEM\Models\Message::create($data);
        return $this->resSuccess();
    }

    /**
     * 会员中心底部广告图
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function bottomImage()
    {
        $repository = new \ShopEM\Repositories\ConfigRepository;
        $config = $repository->configItem('shop', 'banner', $this->GMID);
        $respon['image'] = $config['user_center_bottom_image']['value'] ?? [];
        return $this->resSuccess($respon);
    }

    /**
     * 会员分享记录
     *
     * @Author hfh_wind
     * @return \Illuminate\Http\JsonResponse
     */
    public function userShare(Request $request)
    {
        $data['user_id'] = $this->user->id;

        if (!$request->has('share_type')) {
            return $this->resFailed(414, '分享类型为空');
        }

        $data['share_type'] = $request->share_type;
        $data['fid'] = $request->fid;

        $is_push = true;
        $event = '';
        //处理不同的分享内容
        if ($data['share_type'] == 'goods') {
            $day = date('Y-m-d H:i:s');
            $count = UserShareLog::whereDate('created_at', $day)->where('user_id',
                $data['user_id'])->where('share_type', 'goods')->count();
            if ($count >= 3) {
                $is_push = false;
            } else {
                $event = $this->user->mobile . '分享了商品';
            }
        } else {
            if ($data['share_type'] == 'bargains') {
                $count = UserShareLog::where('user_id', $data['user_id'])->where('share_type', 'bargains')->where('fid',
                    $data['fid'])->count();
                if ($count) {
                    $is_push = false;
                } else {
                    $bargains = ActivityBargains::find($data['fid']);
                    $bargain_number = isset($bargains->bargain_number) ? '-' . $bargains->bargain_number . '-' : '';
                    $event = $this->user->mobile . $bargain_number . '分享砍价活动';
                }
            }
        }

        //推送到CRM
        if ($is_push) {
            $params = [
                'mobile'       => $this->user->mobile,
                'group_number' => 'Share',
                'score'        => '',
                'event'        => $event,
                'user_id'      => $data['user_id'],
            ];
            AddEventToCrm::dispatch($params);
        }
        UserShareLog::create($data);

        return $this->resSuccess();
    }



    /**
     * [search 筛选会员列表]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function team(Request $request)
    {
        $input_data['pid'] = $this->user->id;
        $input_data['is_woa'] = request('is_woa', 0);
        $per_page = request('per_page', config('app.per_page'));

        $model = new UserAccount();
        $lists = $model->select('user_accounts.mobile', 'user_accounts.id', 'user_accounts.created_at', 'wx_userinfos.headimgurl','wx_userinfos.nickname')
            ->leftJoin('wx_userinfos', 'user_accounts.id', '=', 'wx_userinfos.user_id')
            ->where($input_data)
            ->orderBy('user_accounts.created_at', 'desc')
            ->paginate($per_page);

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        if ($lists) {
            $lists = $lists->toArray();
            foreach ($lists['data'] as $k => $v) {
                $number = $k + 1 + ($lists['current_page'] - 1) * $per_page;
                $lists['data'][$k]['number'] = $number;
                $lists['data'][$k]['created_at'] = date('Y-m-d', strtotime($v['created_at']));
                $lists['data'][$k]['mobile'] = $v['mobile'] ? substr_replace($v['mobile'], '****',3, 4) : '';
                if(preg_match("/^1[34578]\d{9}$/", $v['nickname'])){
                    //如果是手机号码
                    $lists['data'][$k]['nick_name'] = substr_replace($v['nickname'], '****',3, 4);
                }
            }
        }

        $listShowFields = [
            ['key' => 'number', 'dataIndex' => 'number', 'title' => '序号'],
            ['key' => 'headimgurl', 'dataIndex' => 'headimgurl', 'title' => '头像'],
            ['key' => 'nickname', 'dataIndex' => 'nickname', 'title' => '昵称'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '入队时间'],
        ];
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $listShowFields,
        ]);
    }


    /**
     * 获取微信服务消息模板(单个)
     * @Author hfh_wind
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function GetSubscribeTemplate(Request $request)
    {
        $id = $request['id']??0;
        if ($id <= 0 ) {
            return $this->resFailed(414, '参数错误');
        }
        $info=WxMiniSubscribeMessages::find($id);
        if (empty($info)) {
            return $this->resFailed(700, '数据找不到');
        }

        return  $this->resSuccess($info);
    }

    public function getSubscribe(Request $request)
    {
        if ($request->template == 'v1') 
        {
            $info['template_data'] = [
                array('title' => '订单发货提醒', 'template_id' => 'CDMyJft3zhlrVIA3wjKnsG2o3HomRodupu46rPuz7OY' ),
                array('title' => '账户积分变动提醒', 'template_id' => 'RZgJavgr2moTPjjL1a2x92G35t3Y0UuMd9ID4n6MBjI' ),
            ];
            return $this->resSuccess($info);
        }
        return $this->resSuccess();
    }


}
