<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-04-29 14:43:58
 * @version 	V1.0
 */

namespace ShopEM\Services;

use Illuminate\Support\Facades\Cache;
use EasyWeChat\Factory;

class WechatService
{

    protected $config;

	public function __construct(){
		// $this->config = [
		// 	'appid'=>env('WECHAT_APPID'),
		// 	'secret'=>env('WECHAT_SECRET'),
		// ];
		// $this->access_token = $this->getAccessToken();
        $config = [
            'app_id' => env('WECHAT_APPID'),
            'secret' => env('WECHAT_SECRET'),

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            //...
        ];
        $this->app = Factory::officialAccount($config);//配置公众号信息
        $this->config = $config;
    }

    /**
     * [getAccessToken 获取access_token]
     * @Author mssjxzw
     * @return [type]  [description]
     */
    public function getAccessToken()
    {
    	$config = $this->config;
    	$time = now()->addSeconds(7200);

     //    $access_token = Cache::remember('access_token', $time, function () use ($config) {
     //        $api_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $config['app_id'] . "&secret=" . $config['secret'];

     //        $json = file_get_contents($api_url);

     //        $result = json_decode($json, true);

     //        return $result['access_token'];

     //    });
    	// return $access_token;
        $api_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $config['app_id'] . "&secret=" . $config['secret'];

        $json = file_get_contents($api_url);

        $result = json_decode($json, true);

        return $result['access_token'];
        
    }

    /**
     * [getApiSdk 获取jsapi的配置]
     * @Author mssjxzw
     * @param  [type]  $api [description]
     * @param  [type]  $url [description]
     * @return [type]       [description]
     */
    public function getApiSdk($api,$url)
    {
        $this->app->jssdk->setUrl($url);
        return $this->app->jssdk->buildConfig($api, true,false,false);
    }
}