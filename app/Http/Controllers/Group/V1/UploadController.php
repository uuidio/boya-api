<?php
/**
 * @Filename UploadController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\AlbumPic;
use ShopEM\Services\Upload\UploadImage;
use Illuminate\Http\Request;
use ShopEM\Services\Upload\UploadApk;
use ShopEM\Models\AppVersions;
use Illuminate\Support\Facades\Storage;

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
        $data['shop_id'] = 0;
        $data['class_id'] = 0;
        $data['gm_id'] = 0;
        $imageInfo = AlbumPic::create($data);

        if(!empty($imageInfo)) {
            return $this->resSuccess(['pic_url' => $imageInfo->pic_url]);
        }

        return $this->resFailed(603, errorMsg(603));
    }

    /**
     * 上传apk
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apk(Request $request)
    {
        ini_set('max_input_time', 3000);
        ini_set('max_execution_time ', 3000);

        $uploadVideo = new UploadApk($request);

        $res = $uploadVideo->save();

        if(isset($res['errorcode']) && $res['errorcode'] > 0) {
            return $res;
        }

        $data['url'] = $res['result']['apk_url'];
        $data['file_name'] = $res['result']['apk_name'];
        #$data['versions'] = $request['versions'];
        #$data['content'] = $request['content'];
        #AppVersions::create($data);

        #$new = AppVersions::orderBy('id', 'desc')->first();

        return $this->resSuccess('https://lanlink.smartconns.com/uploads/'.$data['url']);
    }

    /**
     *
     *
     * @Author linzhe
     */
    public function versionsAdd(Request $request)
    {
        $data = $request->only('url','versions','content');

        $hasVersions = AppVersions::where('versions',$data['versions'])->count();
        if($hasVersions){
            return $this->resFailed(702, '版本号已存在');
        }
        AppVersions::create($data);

        return $this->resSuccess();
    }



    /**
     * 获取最新apk
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apkGet(Request $request)
    {
        $data = AppVersions::orderBy('id', 'desc')->first();

        return $this->resSuccess($data);
    }
}