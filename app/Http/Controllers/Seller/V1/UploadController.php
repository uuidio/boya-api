<?php
/**
 * @Filename UploadController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\AlbumPic;
use ShopEM\Services\Upload\UploadImage;
use Illuminate\Http\Request;
use ShopEM\Services\WechatService;

class UploadController extends BaseController
{
    /**
     * 上传图片
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function image(Request $request)
    {
        $uploadImage = new UploadImage($request);

        $res = $uploadImage->save();

        if(isset($res['code']) && $res['code'] > 0) {
            return $res;
        }

        $data = $res['result'];
        $data['shop_id'] = $this->shop->id;
        $data['class_id'] = 0;
        $data['gm_id'] = $this->GMID;

        $imageInfo = AlbumPic::create($data);

        if(!empty($imageInfo)) {
            return $this->resSuccess(['pic_url' => $imageInfo->pic_url]);
        }

        return $this->resFailed(603, errorMsg(603));
    }


    public function wechatImage(Request $request)
    {
        if (!isset($request->media_id)) {
            return $this->resFailed(603, errorMsg(603));
        }
        $media_id = $request->media_id;

        $service = new WechatService();
        $token = $service->getAccessToken();
        $api_url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$media_id;
        $weixinFile = $this->downloadWeixinFile($api_url);
        
        $uploadImage = new UploadImage($request);
        $res = $uploadImage->createImage($weixinFile['body']);
        if(isset($res['code']) && $res['code'] > 0) {
            return $res;
        }

        $data = $res['result'];
        $data['shop_id'] = $this->shop->id;
        $data['class_id'] = 0;
        $data['gm_id'] = $this->GMID;

        $imageInfo = AlbumPic::create($data);
        if(!empty($imageInfo)) {
            return $this->resSuccess(['pic_url' => $imageInfo->pic_url]);
        }

        return $this->resFailed(603, errorMsg(603));
        
        // return $result['access_token'];
    }

    public function downloadWeixinFile($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);    
        curl_setopt($ch, CURLOPT_NOBODY, 0);    //只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array('header' => $httpinfo), array('body' => $package)); 
        return $imageAll;
    }
}