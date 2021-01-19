<?php

Route::namespace('ShopEM\Http\Controllers\OpenApi\V1')->group(function () {
    Route::get('/token', 'AuthController@issueToken');
});


Route::namespace('ShopEM\Http\Controllers\OpenApi\V1')->middleware(\ShopEM\Http\Middleware\OpenApiAuth::class)->group(function () {
    Route::post('/search/trades', 'TradeController@fetchTrade')->name('openapi.trades');                //  获取订单
    Route::post('/search/refunds', 'TradeController@fetchRefundTrade')->name('openapi.refunds');        //  获取退货单
    Route::post('/callback/trade/stock', 'TradeController@synStockLog')->name('openapi.synStockLog');   //  线下库存回传
});
