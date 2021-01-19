<?php
/**
 * @Filename        User.php
 *
 */
namespace ShopEM\Services\User;

use ShopEM\Jobs\TradePush;
use ShopEM\Jobs\UpdateCrmUserInfo;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserProfile;
use ShopEM\Services\YitianGroupServices;

class UserPassport
{


    /**
     * 注册一个新的账号
     *
     * @param array $data 注册的数据
     *
     * @return bool
     */
    public static function signupUser($data)
    {
        //检查数据安全

        $type = self::checkLoginNameType($data['username']);

        //检查注册的账号是否合法
        self::checkSignupAccount(trim($data['username']), $type);

        $account[$type] = trim($data['username']);

        if ($type == 'mobile') {
            $account['login_account'] = 'shopem' . time();
        }

        $account['password'] = bcrypt($data['password']);

        return $account;
    }


    /**
     * 验证登录注册用户名类型
     *
     * 获取前台注册登录用户类型(用户名,邮箱，手机号码)
     *
     * @param  string $account
     * @return string
     */
    public static function checkLoginNameType($loginName)
    {

        if ($loginName && strpos($loginName, '@')) {
            if (!preg_match("/^[a-z\d][a-z\d_.]*@[\w-]+(?:\.[a-z]{2,})+$/", $loginName)) {
                throw new \LogicException('请输入正确的邮箱地址');
            }
            $type = 'email';
        } elseif (preg_match("/^1[3456789]{1}[0-9]{9}$/", $loginName)) {
            $type = 'mobile';
        } else {
            $type = 'login_account';
        }
        return $type;
    }


    /**
     * @brief 检查注册账号合法性
     *
     * @param $account 账号
     *
     * @return bool
     */
    public static function checkSignupAccount($account, $type)
    {
        if (empty($account)) {
            throw new \LogicException('请输入用户名');
        }

        //获取到注册时账号类型
        switch ($type) {
            case 'login_account':
                if (strlen(trim($account)) < 4) {
                    throw new \LogicException('登录账号最少4个字符');
                } elseif (strlen($account) > 30) {
                    throw new \LogicException('登录账号过长，请换一个重试');
                }

                if (is_numeric($account)) {
                    throw new \LogicException('登录账号不能全为数字');
                }

                if (!preg_match('/^[\w\-\x{4E00}-\x{9FA5}]*$/ui', trim($account))) {
                    throw new \LogicException('登录名仅支持英文字母，数字，中文，"_"和"-"');
                }
                break;
            case 'email':
                if (!preg_match('/^(?:[a-z\d]+[_\-\+\.]?)*[a-z\d]+@(?:([a-z\d]+\-?)*[a-z\d]+\.)+([a-z]{2,})+$/i', trim($account))) {
                    throw new \LogicException('邮件格式不正确');
                }
                break;
            case 'mobile':
                break;
        }

        //判断账号是否存在
        if (self::isExistsAccount($account, null, $type)) {
            $noticeMsg = [
                'login_account' => '该账号已经被占用，请换一个重试',
                'email' => '该邮箱已被注册，请更换一个',
                'mobile' => '该手机号已被注册，请更换一个',
            ];
            throw new \LogicException($noticeMsg[$type]);
        }

        return true;
    }//end function




    /**
     * @brief 判断前台用户名是否存在
     *
     * @param string $account
     *
     * @return
     */
    public static function isExistsAccount($account, $userId = null, $type = null)
    {
        if (empty($account)) {
            throw new \LogicException('验证数据不能为空');
        }

        if (!$type) {
            $type = self::checkLoginNameType($account);
        }
        $filter[$type] = $account;

        if ($userId) {
            $filter['user_id'] = $userId;
        }

        $flag = UserAccount::where($filter)->count();

        return $flag ? true : false;
    }



    /**
     * 修改会员信息
     *
     * @Author djw
     * @param $userId
     * @param $data
     * @return bool
     */
    public static function modifyUserProfiles($userId,$data)
    {
        $model = new \ShopEM\Models\WxUserinfo();
        $profile = $model->select('id','birthday','sex','nickname','headimgurl')->where('user_id', $userId)->first();
        $filter = [];
        if (!$profile) {
            $user = UserAccount::where('id',$userId)->first();
            $profile = $model->select('id','birthday','sex','nickname','headimgurl')->where('openid', $user['openid'])->first();
            if ($profile && $user['openid']) {
                $profile->update(['user_id' => $user['id']]);
            } else {
                $profile = $model->create(['user_id' => $user['id'], 'openid' => $user['openid'], 'sex' => 0]);
            }
        }
//        $filter['birthday'] = $profile['birthday'];
        if (isset($data['birthday']) && $data['birthday']) {
            $filter['birthday'] = strtotime($data['birthday']);
        }
        $sex = [0,1,2,'0','1','2'];
//        $filter['sex'] = $profile['sex'];
        if (isset($data['sex']) && in_array($data['sex'], $sex)) {
            $filter['sex'] = $data['sex'];
        }
//        $filter['nickname'] = $profile['nickname'];
        if (isset($data['name']) && self::checkAccountName($data['name'])) {
            $filter['nickname'] = $data['name'];
        }
//        $filter['headimgurl'] = $profile['headimgurl'];
        if (isset($data['head_pic']) && $data['head_pic']) {
            $filter['headimgurl'] = $data['head_pic'];
        }

        if (isset($data['email']) && $data['email']) {
            $filter['email'] = $data['email'];
        }

        if (isset($data['real_name']) && $data['real_name']) {
            $filter['real_name'] = $data['real_name'];
        }

        if ($filter) {
            $filter['is_update_info'] = 1;
            $res = $profile->update($filter);
            if ($res) {
                // $user = UserAccount::where('id',$userId)
                //     ->select('mobile','card_code','yitian_id')
                //     ->first()
                //     ->toArray();
                $info = [
                    'real_name'    => $data['real_name'] ?? '',
                    'nick_name'    => $data['name'] ?? '',
                    'email'        => $data['email'] ?? '',
                    'gender'       => $data['sex'] ?? '',
                    'dateOfBirth'  => $data['birthday'] ?? '',
                    'user_id'      => $userId,
                ];
                UpdateCrmUserInfo::dispatch($info);
            }
        }
        return [
            'is_update_info' => 1,
            'real_name'      => $data['real_name'] ?? '',
            'birthday'       => $data['birthday'] ?? '',
            'sex'            => $data['sex'] ?? 0,
            'email'          => $data['email'] ?? '',
        ];
    }

    /**
     * @brief 检查注册昵称合法性
     *
     * @param $name 昵称
     *
     * @return bool
     */
    public static function checkAccountName($name, $userId = false)
    {
        if (empty($name)) {
            throw new \Exception('请输入昵称');
        }
        if (strlen(trim($name)) < 4) {
            throw new \Exception('昵称最少4个字符');
        } elseif (strlen($name) > 30) {
            throw new \Exception('昵称过长，请换一个重试');
        }

        if (!preg_match('/^[\w\-\x{4E00}-\x{9FA5}]*$/ui', trim($name))) {
            throw new \Exception('昵称仅支持英文字母，数字，中文，"_"和"-"');
        }

       /* //判断账号是否存在
        $filter['name'] = $name;
        if ($userId) {
            $filter[] = ['id', '!=', $userId];
        }

        $flag = UserProfile::where($filter)->count();

        if ($flag) {
            throw new \Exception('昵称已存在');
        }*/

        return true;
    }



}
