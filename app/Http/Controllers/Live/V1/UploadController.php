<?php
/**
 * @Filename UploadController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Live\V1;

use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\AlbumPic;
use ShopEM\Services\Upload\UploadImage;
use Illuminate\Http\Request;
use ShopEM\Services\WebSocketService;

class UploadController extends BaseController
{
    /**
     * 上传图片
     *
     * @Author linzhe
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
        $data['shop_id'] = 0;
        $data['class_id'] = 0;
        $imageInfo = AlbumPic::create($data);

        if(!empty($imageInfo)) {
            return $this->resSuccess(['pic_url' => $imageInfo->pic_url]);
        }

        return $this->resFailed(603, errorMsg(603));
    }

}