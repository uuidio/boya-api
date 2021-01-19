<?php

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Services\WechatService;

class WechatController extends BaseController
{
    public function getApiSdk(Request $request)
    {
        $model = new WechatService();
        return $this->resSuccess($model->getApiSdk($request->api,urldecode($request->url)));
    }
}
