<?php


namespace ShopEM\Services\BusinessCloud;


use Exception;
use GuzzleHttp\Client;
use ShopEM\Models\Payment;
use ShopEM\Models\UserAccount;

class SdkService
{
    private $url;
    /**
     * @var string
     */
    private $pfx;
    /**
     * @var string
     */
    private $cert;
    /**
     * @var string
     */
    private $backUrl;
    private $secretKey;
    private $appId;
    private $debug;
    private $version;
    private $log_file;
    private $log_line = 100;
    private $http;
    private $pwd;
    private $vspCusid;
    private $industryCode;
    private $industryName;
    private $subAppId;

    public function __construct()
    {
        $this->url = env('BC_URL','http://test.allinpay.com/op/gateway');
        $this->pfx = config_path(env('BC_PFX','cert/business_cloud/1581648210684.pfx'));
        $this->cert = config_path(env('BC_CERT','cert/business_cloud/TLCert-test.cer'));
        $this->secretKey = env('BC_SECRET_KEY','WaHVZNHZYX3v4si1bBTVseIwEMPMcKzL');
        $this->appId = env('BC_APP_ID','1581648210684');
        $this->debug = env('BC_DEBUG',false);
        $this->version = env('BC_VERSION','1.0');
        $this->pwd = env('BC_PWD','123456');
        $this->vspCusid = env('BC_VSP_CUS_ID','123456');
        $this->subAppId = env('BC_SUB_APP_ID','123456');
        $this->industryCode = env('BC_INDUSTRY_CODE','19');
        $this->industryName = env('BC_INDUSTRY_NAME','其他');
        $this->backUrl = env('APP_URL').env('BC_NOTIFY','/payment/bc_notify');
        $this->http = new Client();
    }

    /**
     * [创建会员]
     * @param UserAccount $account
     * @return bool|mixed|string
     */
    public function createUser(UserAccount $account)
    {
        if (!isset($account->login_account)) return false;
        return $this->request('allinpay.yunst.memberService.createMember',[
            'bizUserId' =>  $account->login_account,
            'memberType'=>  3,
            'source'    =>  1,
        ],true);
    }

    /**
     * [绑定openid]
     * @param UserAccount $account
     * @return bool|mixed|string
     */
    public function bindOpenid(UserAccount $account)
    {
        if (!isset($account->login_account)) return false;
        return $this->request('allinpay.yunst.memberService.applyBindAcct',[
            'bizUserId'     =>  $account->login_account,
            'operationType' =>  'set',
            'acctType'      =>  'weChatMiniProgram',
            'acct'          =>  $account->openid,
        ],true);
    }

    /**
     * [查询会员]
     * @param UserAccount $account
     * @return bool|mixed|string
     */
    public function fetchUserInfo(UserAccount $account)
    {
        if (!isset($account->login_account)) return false;
        return $this->request('allinpay.yunst.memberService.getMemberInfo',['bizUserId' => $account->login_account]);
    }

    /**
     * [获取支付配置--消费申请接口]
     * @param Payment $payment
     * @param UserAccount $user
     * @return bool|mixed|string
     */
    public function fetchPayConfig(Payment $payment, UserAccount $user)
    {
        $data = [
            'payerId'       =>  $user->login_account,
            'recieverId'    =>  '#yunBizUserId_B2C#',
            'bizOrderNo'    =>  $payment->payment_id,
            'amount'        =>  $payment->amount * 100,
            'fee'           =>  0,
            'validateType'  =>  0,
            'backUrl'       =>  $this->backUrl,
            'industryCode'  =>  $this->industryCode,
            'industryName'  =>  $this->industryName,
            'source'        =>  1,
            'payMethod'     =>  [
                'WECHATPAY_MINIPROGRAM_ORG' =>  [
                    'vspCusid'  =>  $this->vspCusid,
//                    'subAppid'  =>  $this->subAppId,
                    'limitPay'  =>  '',
                    'amount'    =>  $payment->amount * 100,
                    'acct'      =>  $user->openid,
                ],
            ],
        ];
        return $this->request('allinpay.yunst.orderService.consumeApply',$data,true);
    }

    /**
     * [请求推送]
     * @param $method
     * @param $param
     * @param bool $makeLog
     * @return bool|mixed|string
     */
    public function request($method,$param,$makeLog = false)
    {
        $request["appId"] = $this->appId;
        $request["method"] = $method;
        $request["charset"] = "utf-8";
        $request["format"] = "JSON";
        $request["signType"] = "SHA256WithRSA";
        $request["timestamp"] = date("Y-m-d H:i:s", time());
        $request["version"] = $this->version;
        $request["bizContent"] = json_encode($param);
        $request["sign"] = $this->sign($request);
        try {
            if ($this->debug) $this->log("[请求参数]", json_encode($request));
            $res = $this->http->request('POST',$this->url,['form_params' => $request])->getBody()->getContents();
            if ($this->debug) $this->log("[返回参数]", $res);
            $res = $this->checkResult($res);
            if ($makeLog) {
                workLogBuilder([
                    'status'=>  1,
                    'params'=>  $request,
                    'res'   =>  $res,
                ],'bc','push');
            }
        } catch (Exception $exception) {
            if ($makeLog) {
                workLogBuilder([
                    'status'=>  2,
                    'params'=>  $request,
                    'res'   =>  $exception->getMessage(),
                ],'bc','push');
            }
            return $exception->getMessage();
        }
        return $res;
    }

    /**
     *检查返回的结果是否合法;
     * @param $result
     * @return bool|mixed
     */
    private function checkResult($result)
    {
        $arr = json_decode($result,true);
        $sign = $arr['sign'];
        unset($arr['sign']);
        $this->asciiSort($arr);
        $str = json_encode($arr, JSON_UNESCAPED_UNICODE);
        $success = false;
        if ($sign != null) {
            $success = $this->verify($this->cert,$str,base64_decode($sign));
        }
        if ($success) {
            return $arr;
        }
        if ($this->debug) $this->log("[返回结果不合法]", '');
        return $success;
    }

    /**
     * [foo 对返回数据按照第一个字符的键值ASCII码递增排序]
     * @param $ar
     */
    public function asciiSort(&$ar) {
        if(is_array($ar)) {
            ksort($ar);
            foreach($ar as &$v) $this->asciiSort($v);
        }
    }

    /**
     * [签名算法]
     * @param $strRequest
     * @return string
     */
    public function sign($strRequest)
    {
        unset($strRequest['signType']);
        $strRequest = array_filter($strRequest);//剔除值为空的参数
        ksort($strRequest);
        $sb = '';
        foreach ($strRequest as $entry_key => $entry_value) {
            $sb .= $entry_key . '=' . $entry_value . '&';
        }
        $sb = trim($sb, "&");
        if ($this->debug) $this->log("[待签名值]", $sb);
        $privateKey = $this->getPrivateKey();
        if (openssl_sign(utf8_encode($sb), $sign, $privateKey, OPENSSL_ALGO_SHA256)) {//SHA256withRSA密钥加签
            $sign = base64_encode($sign);
            return $sign;
        } else {
            echo "sign error";
            exit();
        }
    }

    /**
     *验证返回的数据的合法性
     * @param $publicKeyPath
     * @param $text
     * @param $sign
     * @return bool
     */
    private function verify($publicKeyPath, $text, $sign)
    {
        $pubKeyId = openssl_get_publickey(file_get_contents($publicKeyPath));
        $flag = (bool) openssl_verify($text, $sign, $pubKeyId, "sha256WithRSAEncryption");
        openssl_free_key($pubKeyId);
        if ($this->debug) $this->log("[验证签名值]", $text.' -- '.base64_encode($sign));
        return $flag;
    }

    /**
     * [encryptAES AES-SHA1PRNG加密算法]
     * @param $string
     * @return false|string
     */
    public function encryptAES($string){
        //AES加密通过SHA1PRNG算法
        $key = substr(openssl_digest(openssl_digest($this->secretKey, 'sha1', true), 'sha1', true), 0, 16);
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        $data = strtoupper(bin2hex($data));
        return $data;
    }

    /**
     * [encryptAES AES-SHA1PRNG解密算法]
     * @param $string
     * @return false|string
     */
    public function decryptAES($string)
    {
        $key = substr(openssl_digest(openssl_digest($this->secretKey, 'sha1', true), 'sha1', true), 0, 16);
        return openssl_decrypt(hex2bin($string), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }

    /**
     * [调试日志]
     * @param $label
     * @param $content
     */
    public function log($label,$content)
    {
        $path = storage_path('logs/business_cloud_push.log');
        $data = '[' . date('Y-m-d H:i:s') . "]  [$label]  $content" . PHP_EOL;
        if (!file_exists($path) || count($file = file($path)) < $this->log_line) {
            file_put_contents(storage_path('logs/business_cloud_push.log'), $data, FILE_APPEND);
        } else {
            $file[] = $data;
            array_shift($file);
            file_put_contents(storage_path('logs/business_cloud_push.log'), implode('',$file));
        }
    }

    /**
     * [获取密钥字符串]
     * @return mixed|string
     */
    private function getPrivateKey()
    {
        if (!file_exists($this->pfx)) return '';
        $priKey = file_get_contents($this->pfx);
        if (openssl_pkcs12_read($priKey, $certs, $this->pwd)) {
            return $certs['pkey'];
        } else {
            return '';
        }
    }
}
