<?php

namespace ShopEM\Models;

use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use ShopEM\Repositories\UserAccountRepository;

class UserAccount extends Authenticatable
{
    use HasApiTokens;
    protected $guarded = [];
    protected $appends = ['new_yitian_user_text','default_gm_name','default_gm_id', 'user_register_platform',
        'child_num', 'role_type', 'nick_name', 'related' ,'partner'];

    public function findForPassport($username)
    {
        return $this->where('login_account', $username)->orwhere('email', $username)->orwhere('mobile', $username)->first();
    }

    /**
     * 检查手机号是否存在
     *
     * @Author moocde <mo@mocode.cn>
     * @param $phone
     * @return mixed
     */
    static public function checkExistPhone($phone)
    {
        return self::where('mobile', $phone)->count();
    }

    /**
     * 手机验证码登录
     *
     * @Author moocde <mo@mocode.cn>
     * @param                       $phone
     * @param                       $code
     * @param UserAccountRepository $repository
     */
    static public function smsCodeLogin($phone, $code)
    {
        if (self::checkExistPhone($phone) > 0) {
            self::where('mobile', $phone)->update(['sms_code' => md5($code)]);
        } else {
            $account = [
                'login_account' => $phone,
                'mobile'        => $phone,
                'password'      => bcrypt($phone),
                'sms_code'      => md5($code),
            ];

            $user = self::create($account);

            $repository = new UserAccountRepository();
            $repository->createUserDeposit($user->id);
            $repository->createUserPoint($user->id);
        }
    }


    public function getNewYitianUserTextAttribute()
    {
        return $this->new_yitian_user ? '是' : '否';
    }

    /**
     * 追加默认项目名称
     * @Author nlx
     * @return string
     */
    public function getDefaultGmNameAttribute()
    {
        $text = '未设置';
        $gm = UserRelYitianInfo::where('user_id',$this->id)->where('default',1)->select('gm_id')->first();
        if (!empty($gm)) {
            $text = $gm->gm_name;
        }
        return $text;
    }

    /**
     * 追加默认项目
     * @Author nlx
     * @return string
     */
    public function getDefaultGmIdAttribute()
    {
        $gm_id = UserRelYitianInfo::where('user_id',$this->id)->where('default',1)->value('gm_id');
        return !empty($gm_id)? $gm_id : 0 ;
    }

    /*public function getBirthdayAttribute($value)
    {
        return $value ? date('Y-m-d', $value) : null;
    }*/


    /**
     * 追加会员已注册的项目
     * @Author Huiho
     * @return string
     */
    public function getUserRegisterPlatformAttribute()
    {
        $gm_id = UserRelYitianInfo::where('user_id',$this->id)->select('gm_id')->get()->toArray();

        foreach ($gm_id as $key => $value)
        {
            $platform_name[$key] = $value['gm_name'];
        }

        if(empty($platform_name))
        {
            $platform = '';
        }
        else
        {
            $platform = implode(';', $platform_name);
        }
        return !empty($platform)? $platform : '--';
    }


    /**
     * 追加推广下级
     * @Author hfh_wind
     * @return int
     */
    public function getChildNumAttribute()
    {
        return DB::table('user_accounts')->where('pid', $this->id)->count();
    }


    /**
     * 追加推物人数
     * @Author hfh_wind
     * @return intid
     */
    public function getRelatedAttribute()
    {
        $relatedLogs = RelatedLogs::where(['pid' => $this->id])->count();
        return $relatedLogs;
    }


    /**
     * 追加上级
     * @Author hfh_wind
     * @return intid
     */
    public function getPartnerAttribute()
    {
        $partner=self::where('id', $this->partner_id)->select('mobile')->first();
        return $partner['mobile']??'';
    }


    /**
     * 追加昵称
     * @Author hfh_wind
     * @return int
     */
    public function getNickNameAttribute()
    {
        $res = WxUserinfo::where('user_id', $this->id)->select('nickname')->first();

        return $res['nickname']??'';
    }


    /**
     * 追加角色
     * @Author hfh_wind
     * @return int
     */
    public function getRoleTypeAttribute()
    {
        $text = '';
        switch ($this->partner_role) {
            case 0;
                $text = "普通会员";
                break;
            case 1;
                $text = "推广员";
                break;
            case 2;
                $text = "小店";
                break;
            case 3;
                $text = "分销商";
                break;
            case 4;
                $text = "经销商";
                break;
        }

        return $text;
    }
}
