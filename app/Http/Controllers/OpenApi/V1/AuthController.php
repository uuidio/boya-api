<?php

namespace ShopEM\Http\Controllers\OpenApi\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Controller;
use ShopEM\Models\OpenapiAuth;

class AuthController extends Controller
{
    public function issueToken(Request $request, OpenapiAuth $authObj)
    {
        $sign = $request->header('Sign');
        $appid = $request->get('appid','');
        $time = $request->get('timestamp','');
        $nostr = $request->get('nostr','');

        if ($appid && $time && $nostr && $sign) {
            $auth = $authObj->where('appid', $appid)->where('status',1)->first();
            $str = md5($appid.$nostr.$auth->secret.$time);
            if ($str !== $sign) return $this->resFailed(413,'签名错误');
            if (!empty($auth->start) && !empty($auth->end) && !isInTime($auth->start,$auth->end)) return $this->resFailed(412,'appid未生效');
            $token = hash('sha256', $str);
            Cache::put('open_api_'.$token, json_encode(['appid'=>$auth->appid,'api_auth'=>$auth->api_auth,'gm_auth'=>$auth->gm_auth]), now()->addHours(2));
            return $this->resSuccess(['token'=>$token]);
        } else {
            return $this->resFailed(414,'缺少参数');
        }
    }
}
