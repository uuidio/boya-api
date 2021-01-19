<?php
/**
 * EncryptionTest.php
 */
namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use phpseclib\Crypt\RSA;

class EncryptionTest extends TestCase
{

	//私钥
	const PRIVATE_KEY = "-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQDCkFhC/6ko5fZ5QKAJCtCHypUnvx/HiWyHEXaf300BkKnAzwGx
DQuJhMXBRtRDksAWUFTRfnBkeFzUu/SrA1xGNb6mPudC8UIXTO5uFZ/AFQzwTOkZ
pbTct6OfaEz/3gqM9JZYQVI4a915YWe2/f5RxAYnYEHn18WZ/eOF51dpQwIDAQAB
AoGAIcB6nzzMspyaElTErmzi2fupvlhogev0GMZNxtQs/q2C1UDT8UvrCXMv/yRz
ZDmL+xL6c9E0XCmJKRpmClit4+6JoaYVkVGVdhhFr+Ot+s8K/Ryj79LqHppZk2FR
ZFIQ3U4OkD+qABR1CWLWb6CMiQq2NwLVPwDVVs1iXKQULTECQQDupU0ffp1zyV4Q
bh1IlbIMTpElwyan3M+Z2m7BmXmIVxEVQDpJspOOM0xM9aUzWbgaUav2+r6dXX6r
f/+B+iE5AkEA0LZnET2Px6vYYkSNBtJhgWs2KDpcq0BilOvZsQT0qNqFSVQg7zgR
4nsYhjrYsBdhaLELDGkZMamHJCgrKwxqWwJBAMnS8It/KCfhF/UrOwbE2uQ/mc9m
4I08WDIUonCGnFqqz566R9FF/jZXueKoKINqECHqClYAry4lANiHko3Y/TkCQDqZ
zEyR7WnRvTqyJqqwrUHqOVWINXa76DIKGqBSVOOIH35cSbcBFjxx9YvSv/6JQgdk
FkcQx0sjX1duk9hNbxsCQHbBRa34tRRTnTvxWMy3v3m2AmDpXH4BKxvQ4N4wV5Qs
3JBfgKKVtUdewIe6n9ABRyggO28Ej3auGSQNoQ/oCPQ=
-----END RSA PRIVATE KEY-----";
	//公钥
	const PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCVSe8Sj2dBaBJ75jmG9Tz+ceI3
rwn8NwcHn8grAQlJc6HjRIxWuZ7TX3EM5hjPD70lAZmNRN+ckFqoZyMtRs8HxTwq
5jAoeRaI28jZv/+vg4JZOtzOoTNKVT4+sG0sOBkJDDmhDP6kwz2hMFJEbg6XFIzn
BXpeKmOqekhue1i2gQIDAQAB
-----END PUBLIC KEY-----";

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
		// testLog($result);
		// dd('succ');
    	$data['time'] = time();
    	$data['cardno'] = '1234567890123456789';
    	$data['passwork'] = 'abcdefg';
    	
    	$path=base_path().'\config\web.php';
		$str='abcdefg'; //要声明的字符串
		file_put_contents($path,$str);//把字符串内容存储到web.php中。


    	$json = json_encode($data);
		$encrypt = self::rsaEncrypt($json,self::PUBLIC_KEY);

		dd($encrypt);

		$decrypt = self::rsaDecrypt($encrypt,self::PRIVATE_KEY);
        $this->assertTrue(true);
    }

	/**
     * @param string $string 需要加密的字符串
     * @param string $key 密钥
     * @return string
     */
	public static function encrypt($string, $key)
	{
		// openssl_encrypt 加密不同Mcrypt，对秘钥长度要求，超出16加密结果不变
        $data = openssl_encrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
       	$result = base64_encode($data);

        return $result;
	}


	/**
     * @param string $string 需要解密的字符串
     * @param string $key 密钥
     * @return string
     */
    public static function decrypt($string, $key)
    {
    	$string = base64_decode($string);
        $decrypted = openssl_decrypt($string, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        return json_decode($decrypted,1);
    }

    /** sa加密
     * @param $string
     * @param $key
     * @return string
     */
    public static function rsaEncrypt($string, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);    //选择加密的模式
        return base64_encode($rsa->encrypt($string));    //需要对结果进行base64转码
    }
 
    /** rsa解密
     * @param $encryptStr
     * @param $key
     * @return mixed
     */
    public static function rsaDecrypt($encryptStr, $key)
    {
        $rsa = new RSA();
        $rsa->loadKey($key);
        $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
        return $rsa->decrypt(base64_decode($encryptStr));
    }
}
