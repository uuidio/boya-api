<?php
/**
 * @Filename        CreateQrService.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Services\WeChatMini;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class CreateQrService
{

	/**
	 * 获取微信小程序二维码
	 *
	 * @Author hfh_wind
	 * @return int
	 */
	public function GetWxQr($scene,$page,$gm_id)
	{

		$sevice = new  \ShopEM\Services\SubscribeMessageService();

		$ACCESS_TOKEN=$sevice->getAccessToken(0,$gm_id);


		//构建请求二维码参数
		//path是扫描二维码跳转的小程序路径，可以带参数?type=xxx
		//width是二维码宽度
		$qcode = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=$ACCESS_TOKEN";
//		$qcode = "https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=$ACCESS_TOKEN";
//		$param = json_encode(array("path" => "pagesA/shareHelp/helpDesc?type=transmit", "width" => 150));
		$param = json_encode(array("scene"=>$scene,"page" =>$page , "width" => 150));

		//pagesA/shareHelp/helpDesc
		//POST参数
		$result = $this->httpRequest($qcode, $param, "POST");
		$check=json_decode($result,true);
//		testLog($result);
		//生成二维码

		if(isset($check['errcode'])){
			return  false;
		}

		$fileName = 'images/default/' .md5(time()) .mt_rand(0,9999).'.png';
		$res=Storage::disk('local')->put($fileName, $result);

		if (!$res){
			return  false;
		}
//		$filePath=public_path('uploads/wxqrcode.png');
//		file_put_contents($filePath, $result);//存本地
//		$base64_image = "data:image/jpeg;base64," . base64_encode($result);

		return $filePath=env('APP_URL') . '/uploads/'.$fileName;
//		return env('APP_URL') .'/uploads/wxqrcode.png';
//		return  config('filesystems.disks.oss.domain') . $fileName;
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
	 * 把请求发送到微信服务器换取二维码
	 * @Author hfh
	 * @param $url
	 * @param string $data
	 * @param string $method
	 * @return bool|string
	 */
	public function httpRequest($url, $data = '', $method = 'GET')
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
		if ($method == 'POST') {
			curl_setopt($curl, CURLOPT_POST, 1);
			if ($data != '') {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			}
		}

		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}



}

?>
