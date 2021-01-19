<?php


namespace ShopEM\Services\Xinpoll;


use Illuminate\Support\Facades\Cache;
use ShopEM\Models\UserCashLog;

class Sdk
{
    private $domain;
    private $appId;
    private $appSecret;
    private $appVersion = 'v2.0';
    private $key;
    private $type = 'bf-ecb';

    public function __construct()
    {
        $this->appId = env('XIN_APP_ID','03017636');
        $this->appSecret = env('XIN_APP_SECRET','1032082925959688192');
        $this->key = env('XIN_KEY','|xwE9UtuA0u=(:UF');
        $this->domain = env('XIN_DOMAIN', 'http://integration.yjb.xinshuiguanjia.com/restful');
    }

    public function createAccount(string $name,string $card,string $mobile,int $area = 0)
    {
        if (empty($name) || empty($card)) return false;
        return $this->request($this->domain.'/esign/account/identity',[
            'realname'  =>  $name,
            'cardNo'  =>  $card,
            'personArea'  =>  $area,
            'mobile'  =>  $mobile,
        ],$this->getToken());
    }

    public function checkIdCard(array $data)
    {
        if (
            (!isset($data['realname']) || !$data['realname']) or
            (!isset($data['certNo']) || !$data['certNo']) or
            (!isset($data['authMethod']) || !$data['authMethod']) or
            (!isset($data['IdCardFrontPicBase64']) || !$data['IdCardFrontPicBase64']) or
            (!isset($data['IdCardBackPicBase64']) || !$data['IdCardBackPicBase64'])
        ) {
            return false;
        }

        return $this->request($this->domain.'/esign/verifyCertBase64',$data,$this->getToken());
    }

    public function userSign(string $account, string $picture = '',array $param = [],array $notifyUrl = [])
    {
        if (empty($name) || empty($card)) return false;
        $send = ['accountId'=>$account];
        if ($picture) $send['sealData'] = $picture;
        if ($param) $send['extraData'] = $param;
        if ($notifyUrl) $send['notifyUrl'] = $notifyUrl;
        return $this->request($this->domain.'/esign/userSign',$send,$this->getToken());
    }

    public function idCheckWechat($card)
    {
        return $this->request($this->domain.'/trans/openid/auth',['certNo'=>$card],$this->getToken());
    }

    public function issueWechat($datas,$param = [],$notifyUrl = [],$cmd = 'MMPAY')
    {
        return $this->request($this->domain.'/trans/upload',[
            'datas' =>  $datas,
            'cmd'   =>  $cmd,
            'extraData' =>  $param,
            'notifyUrls'=>  $notifyUrl
        ],$this->getToken());
    }

    public function getPayment($paymentId)
    {
        return $this->request($this->domain.'/trans/bill',['batchNo'=>$paymentId],$this->getToken());
    }

    public function getOrder($orderId)
    {
        return $this->request($this->domain.'/trans/order/query',['orderNo'=>$orderId],$this->getToken());
    }

    private function request($url,$data,$token = '')
    {
        $client = new \GuzzleHttp\Client();
        $send = ['json' => $this->format($data)];
        if ($token) $send['headers'] = ['Authorization'=>'Bearer '.$token];
        $res = json_decode($client->request('post',$url,$send)->getBody()->getContents(),true);
        UserCashLog::create([
            'code'  =>  $res['code'],
            'data'  =>  json_encode($res['data']),
        ]);
        return $res;
    }

    private function getToken()
    {
        if (Cache::has('xinpoll_token')) {
            return Cache::get('xinpoll_token');
        } else {
            $res = $this->request($this->domain.'/oauth/token',[
                'username'  =>  $this->appId,
                'password'  =>  $this->appSecret,
                'appVersion'=>  $this->appVersion,
            ]);
            if ($res['code'] == 1000) {
                Cache::put('xinpoll_token',$res['data']['token'],now()->addHours(2));
                return $res['data']['token'];
            } else {
                workLog($res['data']['error'],'xinpoll','token-error');
                return false;
            }
        }
    }

    private function format($data)
    {
        $encrypt = openssl_encrypt(json_encode($data),$this->type,$this->key);
        $nonce = getRandStr(16);
        $timestamp = getMicroTime();
        $signature = sha1(implode('',$this->sort_ascii([$this->key,$timestamp,$nonce,$encrypt])));
        return [
            'encrypt'   =>  $encrypt,
            'nonce'   =>  $nonce,
            'signature'   =>  $signature,
            'timestamp'   =>  $timestamp,
        ];
    }

    private function sort_ascii(array $data)
    {
        $ascii = [];
        foreach ($data as $key => $string)
        {
            $ascii[$key] = ord($string);
        }
        asort($ascii);
        $return = [];
        foreach ($ascii as $key => $value){
            $return[$key] = $data[$key];
        }
        return $return;
    }
}
