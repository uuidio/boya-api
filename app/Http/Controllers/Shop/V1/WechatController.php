<?php
/**
 * @Filename        WechatController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Services\User\UserPassport;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserProfile;
use EasyWeChat;

class WechatController extends BaseController
{
    protected $wechatOfficial;

    public function __construct(EasyWeChat $easyWeChat)
    {
        $this->wechatOfficial = $easyWeChat::officialAccount();
    }

    /**
     * 发起授权页
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return mixed
     */
    public function oauthRedirect(Request $request)
    {
        $request->session()->put('wechat_oauth_referer', $request->referer);

        return $this->wechatOfficial->oauth->scopes(['snsapi_base'])->redirect();
    }

    /**
     * 授权回调页并获取用户信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function oauthCallback(Request $request)
    {
        $referer = $request->session()->get('wechat_oauth_referer');

        // 获取 OAuth 授权结果用户信息
        $user = $this->wechatOfficial->oauth->user();
        $request->session()->put('openid', $user->id);

        return redirect($referer);
    }

    public function serve()
    {
        return $this->wechatOfficial->server->serve();
    }

    /**
     * [getApiSdk 获取jsapi配置信息]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function getApiSdk(Request $request)
    {
        $model = new \ShopEM\Services\WechatService();
        return $this->resSuccess($model->getApiSdk($request->api,urldecode($request->url)));
    }

    /**
     * [get_code 授权登录第一步：获取code]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function get_code(Request $request)
    {
        $redirect_uri = route('getOpenid');
        if ($request->filled('url')) {
            $redirect_uri = $redirect_uri.'?url='. $request->url;
        }
        $appid = env('WECHAT_APPID');
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $appid . '&redirect_uri=' . urlencode($redirect_uri) . '&response_type=code&scope=snsapi_userinfo&state=STATE&connect_redirect=1#wechat_redirect';
        // header('Location:' . $api_url);
        // exit;
        return redirect($url);
    }

    /**
     * [get_access_token 获取授权登录token及后续步骤]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function get_access_token(Request $request)
    {
        $code = $request->input('code');
        if (!$code) {
            workLog($request->all(),'wx','getCode');
        }
        # code的token为网页授权调用换取回来的验证，只对code有效。
        $api_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?';
        $api_url = $api_url . 'appid=' . env('WECHAT_APPID');
        $api_url = $api_url . '&secret=' . env('WECHAT_SECRET');
        $api_url = $api_url . '&code=' . $code .'&grant_type=authorization_code';
        $result = $this->curl($api_url);
        if (!isset($result['openid'])) {
            workLog($result,'wx','getOpenid');
        }
        $this->save_wx_info($result);
        if ($request->filled('url')) {
            $redirect_url = urldecode($request->url);
            $redirect_url = explode('?', $redirect_url);
            if (isset($redirect_url[1])) {
                $params = explode('&', $redirect_url[1]);
            }
            if (isset($result['openid'])) {
                $params[] = 'openid='.$result['openid'];
            }
            $url = $redirect_url[0].'?'.implode('&', $params);
            return redirect($url);
        }else{
            if (isset($result['openid'])) {
                return redirect('https://shop.hyplmm.com?openid='.$result['openid']);
            }else{
                return redirect('https://shop.hyplmm.com');
            }
        }
    }

    /**
     * [curl curl(get)]
     * @Author mssjxzw
     * @param  [type]  $url [description]
     * @return [type]       [description]
     */
    private function curl($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, false);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        $result = json_decode($data, true);
        return $result;
    }

    /**
     * [getAccount 获取会员账号]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    private function getAccount()
    {
        $account = 'hy'.date('Ymd').'_'.getRandStr(6);
        if (UserPassport::isExistsAccount($account,null,'login_account')) {
            $account = $this->getAccount();
        }
        return $account;
    }

    /**
     * [update_wx_info description]
     * @Author mssjxzw
     * @param  string  $value [description]
     * @return [type]         [description]
     */
    private function save_wx_info($data)
    {
        if (!isset($data['openid'])) {
            return false;
        }
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$data['access_token']}&openid={$data['openid']}&lang=zh_CN";
        $res = json_decode(file_get_contents($url),true);
        if (array_key_exists('privilege', $res)) {
            unset($res['privilege']);
        }
        if (array_key_exists('unionid', $res)) {
            unset($res['unionid']);
        }
        if (array_key_exists('language', $res)) {
            unset($res['language']);
        }
        $model = new \ShopEM\Models\WxUserinfo();
        $res['sex'] = (int)$res['sex'];
        $info = $model->where('openid',$res['openid'])->first();
        if ($info) {
            $s = 0;
            if ($res['nickname'] && $info->nickname != $res['nickname']) {
                $info->nickname = $res['nickname'];
                $s = 1;
            }
            if ($res['province'] && $info->province != $res['province']) {
                $info->province = $res['province'];
                $s = 1;
            }
            if ($res['city'] && $info->city != $res['city']) {
                $info->city = $res['city'];
                $s = 1;
            }
            if ($res['country'] && $info->country != $res['country']) {
                $info->country = $res['country'];
                $s = 1;
            }
            if ($res['headimgurl'] && $info->headimgurl != $res['headimgurl']) {
                $info->headimgurl = $res['headimgurl'];
                $s = 1;
            }
            if ($s) {
                $info->save();
            }
        }else{
            $model->create($res);
        }
    }
}