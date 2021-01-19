<?php
namespace ShopEM\Services;
use SimpleSoftwareIO\QrCode\Facades\QrCode AS QrCodeService;
use ShopEM\Models\QrcodeStore;
use phpseclib\Crypt\RSA;
use ShopEM\Repositories\UserAccountRepository;
use Illuminate\Support\Facades\Cache;

class QrCode
{
    /**
     * 生成二维码
     * @param $request 二维码路径
     * @return string
     */
    public function create($request)
    {
        $time = date('Y-m-d',time());
        $qrCode_path = public_path('qrcodes') . '/' . $time;
        $qrCode_url = 'qrcodes/'.$time;

        $array_dir = explode('/', $qrCode_path);
        $path = '';
        for ($i = 0; $i < count($array_dir); $i++) {
            $path .= $array_dir[$i] . "/";
            if (!file_exists($path)) {
                mkdir($path, 0777);
            }
        }

        $new_path = $qrCode_url . '/' . md5(time()) . '.svg';
        
        \SimpleSoftwareIO\QrCode\Facades\QrCode::generate($request, $new_path);

        return $new_path;
    }

    //钱包支付码
    public function payCode($user_id)
    {
        $user = Cache::remember('cache_key_user_wallets_id_' . $user_id, cacheExpires(), function () use ($user_id) {
            $repository = new UserAccountRepository();
            $user_info = $repository->userWalletInfo($user_id);

            return $user_info;
        });
        if (empty($user->user_id)) {
            Cache::forget('cache_key_user_wallets_id_' . $user_id);
            throw new \Exception("钱包功能服务错误");
        }

        //支付码参数
        $data = [
            'time'          => time(),
            'cust_id'       => $user->tl_id,
            'mobile'        => $user->mobile,
        ];
        $json = json_encode($data);
        //进行公钥加密
        $value = self::rsaEncrypt($json);

        $image_url = QrcodeStore::tempQr($value, 300, true);
        return $image_url;
    }

    /** rsa加密
     * @param $string
     * @param $key
     * @return string
     */
    public static function rsaEncrypt($string)
    {
        $configModel = new \ShopEM\Models\Config;
        $filter = ['page'=> 'key_manage','group'=> 'public_key'];
        $config = $configModel->where($filter)->select('value')->first();
        $key = $config ? $config->value : '';

        $rsa = new RSA();
        $rsa->loadKey($key);
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);    //选择加密的模式
        return base64_encode($rsa->encrypt($string));    //需要对结果进行base64转码
    }
}
