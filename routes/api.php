<?php
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Support\Facades\Route;

Route::prefix('platform/v1')
    ->group(base_path('routes/Platform/V1.php'));

Route::prefix('seller/v1')
    ->group(base_path('routes/Seller/V1.php'));

Route::prefix('shop/v1')
    ->group(base_path('routes/Shop/V1.php'));

Route::prefix('group/v1')
    ->group(base_path('routes/Group/V1.php'));

Route::prefix('openapi/v1')
    ->group(base_path('routes/OpenApi/V1.php'));

Route::prefix('live/v1')
    ->group(base_path('routes/Live/V1.php'));


Route::namespace('ShopEM\Http\Controllers\Shop\V1')->group(function () {
    // wechat oauth
    Route::get('/wechat/oauth_redirect', 'WechatController@oauthRedirect')->middleware(['web']);
    Route::get('/wechat/oauth_callback', 'WechatController@oauthCallback')->middleware(['web']);

    // wechat pay
    Route::get('/payment/wechatpay', 'WxpayController@mpPay')->middleware(['web']);
    Route::get('/payment/wechath5pay', 'WxpayController@wapPay')->middleware(['web']);
    //小程序
    Route::get('/payment/wechatminipay', 'WxpayController@MiniPay')->middleware(['web']);
    //Route::get('/payment/status/{payment_id?}', 'WxpayController@payStatus')->middleware(['web']);
    Route::any('/payment/wx_notify', 'WxpayController@notify');
    Route::any('/payment/wx_gnotify', 'WxpayController@gnotify');

    Route::any('/payment/bc_notify', 'WxpayController@businessNotify');//通商云回调

    Route::any('/sign/xinpoll-notify', 'WxpayController@xinPollNotify');// 佣金宝签约回调
    Route::any('/issue/xinpoll-notify', 'WxpayController@xinPollIssueNotify');// 佣金宝发放回调

    Route::any('/third-wallet/notify', 'UserWalletController@payNotify');// 第三方支付成功回调

});
