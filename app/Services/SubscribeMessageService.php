<?php
/**
 * @Filename    SubscribeMessageService.php
 *
 * @Copyright    Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License    Licensed <http://www.shopem.cn/licenses/>
 * @authors    hfh
 * @date        2019-04-29 14:43:58
 * @version    V1.0
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\Cache;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Redis;
use ShopEM\Models\WxMiniSubscribeMessages;
use ShopEM\Models\WxSubscribeMessagesAuthorizeLog;
use ShopEM\Models\WxUserinfo;

class SubscribeMessageService
{


    //发送订阅消息
    public function SendSubscribeMessage($touser, $template_id, $page, $content,$expire=0)
    {
        //access_token
        $access_token = $this->getAccessToken($expire);

        //请求url
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $access_token;

        //发送内容
        $data = [];

        //接收者（用户）的 openid
        $data['touser'] = $touser;

        //所需下发的订阅模板id
        $data['template_id'] = $template_id;

        //点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
        $data['page'] = $page;

        //模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }
        $data['data'] = $content;

        //跳转小程序类型：developer 为开发版；trial为体验版；formal为正式版；默认为正式版
        $data['miniprogram_state'] = 'formal';

        return self::curlPost($url, json_encode($data));
    }


    /**
     * 获取 微信ACCESS_TOKEN
     *
     * @Author hfh_wind
     * @return mixed
     */
    public function getAccessToken($expire,$gm_id=0)
    {

        $param = Cache::get('gm_platform_'.$gm_id);

        if(empty($param)){
            throw new \Exception('配置参数异常！');
        }

        $config = [
            'app_id' => $param['mini_appid'],
            'secret' => $param['mini_secret'],
        ];

        $redis=new Redis();

        $wx_get_access_token_key='wx_get_access_token_'.$gm_id;
        $access_token=$redis::get($wx_get_access_token_key);

        if(!$access_token ||  $expire){
            $api_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $config['app_id'] . "&secret=" . $config['secret'];

            $json = file_get_contents($api_url);

            $result = json_decode($json, true);

            $access_token=$result['access_token'];

            $redis::setex($wx_get_access_token_key,7000,$access_token);
        }

        return  $access_token;
    }


    //发送post请求
    protected function curlPost($url, $data)
    {
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_SSL_VERIFYPEER] = false;
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $data;
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        $content = json_decode($content, true);

        return $content;
    }


    /**
     * @Author hfh_wind
     */
    public function SendMessageAct($param)
    {
        $res = WxSubscribeMessagesAuthorizeLog::where([
            'subscribe_id' => $param['subscribe_id'],
            'user_id'      => $param['user_id']
        ])->first();

        if (empty($res)) {
            return false;
        }
        $redis = new Redis();

        try {
            $send = WxMiniSubscribeMessages::where(['id' => $param['subscribe_id'], 'enable' => 1])->first();
            if (empty($send)) {
                throw new \LogicException('模板不存在或停用!');
            }

            $user_info = WxUserinfo::where(['user_id'=> $param['user_id'],'user_type'=>1])->select('openid')->first();
            $touser = $user_info['openid'];
            $template_id = $send['template_id'];
            $page = $send['page'];


            $new_data=[];
            $i=-1;
            foreach($send['data']  as $key=>$value){
                $i++;
                $new_data[$key]['value']=$param['data'][$i];
            }

            //如果过期之后就重新请求 access_token
            $expire=$param['expire']??0;

            $content = $new_data;

            $get_send_msg = $this->SendSubscribeMessage($touser, $template_id, $page, $content,$expire);

            if (isset($get_send_msg['errcode']) && $get_send_msg['errcode'] == 0) {
                $key = "subscribe_template_" . $template_id . "_u_" . $param['user_id'];
                $redis::lpop($key);//删除模板次数
                testLog('发送成功');
                testLog($get_send_msg);
            }else{
                //如果过期了重新来请求
                if($get_send_msg['errcode'] =='40001'){
                    $param['expire']=1;
                    $this->SendMessageAct($param);
                }
                testLog($get_send_msg);
            }

        } catch (\Exception $e) {
            testLog("推送消息失败!");
            testLog($e->getMessage());
            throw new \LogicException($e->getMessage());
        }
    }


}