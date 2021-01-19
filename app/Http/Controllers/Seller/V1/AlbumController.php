<?php
/**
 * @Filename AlbumController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Models\AlbumPic;
use ShopEM\Services\PictureServices;

class AlbumController extends BaseController
{
	/**
	 * [pics 获取店铺图片列表]
	 * @Author mssjxzw
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
    public function pics(Request $request)
    {
        $shop_id = $this->shop->id;
        $data = AlbumPic::where('shop_id', $shop_id)->orderBy('id', 'desc')->paginate(config('app.per_page'));

        return $this->resSuccess($data);
    }

    /**
     * [delById 根据id删除图片]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function delById(Request $request)
    {
        $data = $request->only('id');
    	$check = checkInput($data,'delById','pic');
        if($check['code']){
            return $this->resFailed(414,$check['msg']);
        }
        if (is_string($data['id'])) {
        	$data['id'] = explode(',', $data['id']);
        }
        $model = new PictureServices();
    	$res = $model->delById($data['id']);
    	if ($res['code']) {
    		return $this->resFailed(500,$res['msg']);
    	}else{
    		return $this->resSuccess('删除成功');
    	}
    }

    /**
     * [delByUrl 根据url删除图片]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function delByUrl(Request $request)
    {
		$data = $request->only('url');
    	$check = checkInput($data,'delByUrl','pic');
        if($check['code']){
            return $this->resFailed(414,$check['msg']);
        }
    	$model = new PictureServices();
		$res = $model->delByUrl($data['url']);
    	if ($res['code']) {
    		return $this->resFailed(500,$res['msg']);
    	}else{
    		return $this->resSuccess('删除成功');
    	}
    }
}