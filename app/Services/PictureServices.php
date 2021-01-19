<?php
/**
 * @Filename 	
 *
 * @Copyright 	Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License 	Licensed <http://www.shopem.cn/licenses/>
 * @authors 	Mssjxzw (mssjxzw@163.com)
 * @date    	2019-04-15 09:46:01
 * @version 	V1.0
 */
namespace ShopEM\Services;
use ShopEM\Models\AlbumPic;
use Illuminate\Support\Facades\Storage;

class PictureServices {

    /**
     * [delById 根据id删除图片]
     * @Author mssjxzw
     * @param  [type]  $id [description]
     * @return [type]      [description]
     */
    public function delById($id)
    {
    	if (!is_array($id)) {
    		return ['code'=>1,'msg'=>'参数错误'];
    	}
        foreach ($id as $key => $value) {
        	$data = AlbumPic::where('id',$value)->first();
        	if ($data) {
                $data->delete();
            }
            // $url = $data->getOriginal('pic_url');
        	// $this->unl($url);
        }
    	return ['code'=>0,'msg'=>'删除成功'];
    }
    // private function unl($url)
    // {
    // 	if (Storage::disk('local')->exists($url)) {
    // 		Storage::disk('local')->delete($url);
    // 		return true;
    // 	}else{
    // 		return false;
    // 	}
    // }

    /**
     * [delByUrl 根据url删除图片]
     * @Author mssjxzw
     * @param  [type]  $url [description]
     * @return [type]       [description]
     */
    public function delByUrl($url)
    {
    	if (!is_string($url)) {
    		return ['code'=>1,'msg'=>'参数错误'];
    	}
        // $local = Storage::disk('local')->url('');
        $local = config('filesystems.disks.oss.cdnDomain');
        $url = str_replace($local,'',$url);
    	$data = AlbumPic::where('pic_url',$url)->first();
    	if (!$data) {
    		return ['code'=>2,'msg'=>'无此数据'];
        }
    	// $url = $data->getOriginal('pic_url');
    	$data->delete();
    	// $this->unl($url);
    }

}