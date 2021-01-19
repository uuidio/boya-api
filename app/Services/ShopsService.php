<?php
/**
 * @Filename        GoodsService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Services;


use ShopEM\Models\PageView;
use ShopEM\Models\SellerAccount;

class ShopsService
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
        if ($type == 'phone') {
            $account['username'] = $data['username'];
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
            $type = 'phone';
        } else {
            $type = 'username';
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
            case 'phone':
                break;
        }

        //判断账号是否存在
        if (self::isExistsAccount($account, null, $type)) {
            $noticeMsg = [
                'login_account' => '该账号已经被占用，请换一个重试',
                'email' => '该邮箱已被注册，请更换一个',
                'phone' => '该手机号已被注册，请更换一个',
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

        $flag =SellerAccount::where($filter)->count();

        return $flag ? true : false;
    }


    public function getShopType($shop_id)
    {
        $shop3rd = [4,5,6];
        if (in_array($shop_id,$shop3rd)) {
            return 'self3rd';
        }else{
            return 'self';
        }
    }

    /**
     * 获取当前位置到指定位置的距离（Km/公里）精确到2位小数
     * $from = array($post['lon'],$post['lat']); 当前位置经纬度
     * $to= array($post['lon'],$post['lat']); 目的地经纬度
     */
    public function get_distance($from,$to,$km=true,$decimal=2)
    {
        sort($from);
        sort($to);
        $EARTH_RADIUS = 6370.996; // 地球半径系数

        $distance = $EARTH_RADIUS * 2 * asin(sqrt(pow(sin(($from[0] * pi() / 180 - $to[0] * pi() / 180) / 2), 2) + cos($from[0] * pi() / 180) * cos($to[0] * pi() / 180) * pow(sin(($from[1] * pi() / 180 - $to[1] * pi() / 180) / 2), 2))) * 1000;

        if ($km) {
            $distance = $distance / 1000;
        }

        return round($distance, $decimal);
    }



        /**
     * @brief 记录页面浏览日志
     *
     * @param string $ip,string $route,string $type,int $obj_id
     *
     * @return
     */
    public function addPageView( $ip,$route,$type,$obj_id = '0'){
            $page = new PageView();
        try{
            $page->obj_id = $obj_id;
            $page->type = $type;
            $page->visit_ip = $ip;
            $page->current_route = $route;
            $page->save();
        }catch (\Exception $exception) {

        }
    }

}