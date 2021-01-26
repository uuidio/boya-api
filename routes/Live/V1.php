<?php
/**
 * @Filename Live/V1.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

Route::namespace('ShopEM\Http\Controllers\Live\V1')->group(function () {

  
    Route::any('anchor/login', 'PassportController@login')->name('login');  // 主播端登录
    Route::post('anchor/register', 'PassportController@register');  // 主播端注册
    Route::post('anchor/code', 'PassportController@sendLoginCode');  // 主播端验证码
    Route::post('anchor/resetPwd', 'PassportController@resetPwd');    // 验证码重置密码

    Route::post('assistant/login', 'AssistantController@login');    //助理端登录
    Route::post('upload/image', 'UploadController@image'); // 上传图片
    Route::get('live/share', 'LiveController@share');   // 小程序分享

    Route::post('live/endNotify', 'LiveController@streamEndNotifyUrl');   // 断流异步回调
    Route::post('live/recordNotify', 'LiveController@recordNotifyUrl');   // 录制异步回调
    Route::post('live/beginNotify', 'LiveController@treamBeginNotifyUrl');// 推流异步回调
    Route::post('assistant/raffles/result', 'AssistantController@refflesResult');    //开奖

    Route::get('versions/check', 'PassportController@versions');    //版本
});

Route::namespace('ShopEM\Http\Controllers\Live\V1')->middleware('auth:live_users')->group(function () {

    Route::get('live/oauth', 'PassportController@loginOauth');   //
    Route::post('live/begin', 'LiveController@begin');   // 开始直播
    Route::get('live/end', 'LiveController@end');   // 关闭直播
    Route::get('live/record', 'LiveController@liveRecord');   // 开始录制
    Route::get('live/playback', 'LiveController@playback');   // 回放
    Route::post('live/playback/delete', 'LiveController@delPlayback');   // 回放删除

    Route::get('live/audience', 'LiveController@audience');   // 关闭直播

    Route::get('live/close', 'LiveController@closeLive');   // 强制关闭直播

    /*
     * 主播商品
     */
    Route::get('goods/list', 'LiveController@goods');    // 主播端商品列表
    Route::post('goods/save', 'LiveController@saveGoods');    // 主播端商品保存
    Route::post('goods/delete', 'LiveController@delGoods');    // 主播端商品删除
    Route::post('goods/showSave', 'LiveController@showUpdateGoods');    //商品悬浮更新
    Route::get('goods/show', 'LiveController@showGoods');    //商品悬浮显示

    /*
     * 主播会员
     */
    Route::get('anchor/logout', 'PassportController@logout');    // 主播端退出登录
    Route::post('anchor/modify', 'PassportController@modifyUser');    // 主播端信息更新
    Route::get('anchor/detail', 'PassportController@detail');    // 主播端信息

    Route::get('assistan/get', 'LiveController@assistan');    // 主播端信息

    /*
     * 直播预告
     */
    Route::post('foreshow/add', 'ForeshowController@add');    //预告添加
    Route::get('foreshow/list', 'ForeshowController@list');    //预告列表
    Route::post('foreshow/delete', 'ForeshowController@delete');    //预告删除
    Route::post('foreshow/update', 'ForeshowController@update');    //预告更新
    Route::get('foreshow/edit', 'ForeshowController@edit');    //预告详情


    /*
     * 设备端
     */
    Route::post('autocue/classify/add', 'EquipmentController@autocueClassifyAdd');    //提词器分类添加
    Route::get('autocue/classify/list', 'EquipmentController@autocueClassifyList');    //提词器分类列表
    Route::post('autocue/classify/delete', 'EquipmentController@autocueClassifyDel');    //提词器分类删除
    Route::post('autocue/classify/save', 'EquipmentController@autocueClassifySave');    //提词器分类编辑

    Route::post('autocue/add', 'EquipmentController@autocueAdd');    //提词器添加
    Route::get('autocue/list', 'EquipmentController@autocueList');    //提词器列表
    Route::post('autocue/delete', 'EquipmentController@autocueDel');    //提词器删除
    Route::post('autocue/save', 'EquipmentController@autocueSave');    //提词器编辑

    Route::post('tag/add', 'EquipmentController@tagsAdd');    //素材分类添加
    Route::get('tag/list', 'EquipmentController@tagsList');    //素材分类列表
    Route::post('tag/delete', 'EquipmentController@tagsDel');    //素材分类删除
    Route::post('tag/save', 'EquipmentController@tagSave');    //素材分类编辑

    Route::post('tag/image/add', 'EquipmentController@tagsImageAdd');    //素材分类图片添加
    Route::post('tag/image/delete', 'EquipmentController@tagsImageDel');    //素材分类图片删除
    Route::get('tag/image/list', 'EquipmentController@tagsImageList'); //素材分类图片列表

    Route::get('liveTag/image/list', 'EquipmentController@liveTagsImageList'); //素材分类图片列表

    Route::post('tagImageApp/save', 'EquipmentController@tagsImageStatusSave');//app报错选中图片
    Route::get('notice/get', 'EquipmentController@notice');//app报错选中图片
});
Route::namespace('ShopEM\Http\Controllers\Live\V1')->middleware('auth:assistant_users')->group(function () {

    Route::get('assistant/logout', 'AssistantController@logout');    //助理端退出登录
    Route::post('assistant/banned/set', 'AssistantController@setBanned');    //助理端设置禁言
    Route::get('assistant/banned/list', 'AssistantController@banned');    //助理端禁言列表
    Route::post('assistant/banned/cancel', 'AssistantController@cancelBanned');    //助理端取消禁言

    Route::get('assistant/notice/details', 'AssistantController@notice');    //助理端公告详情
    Route::post('assistant/notice/update', 'AssistantController@noticeUpdate');    //助理端公告更改
    Route::get('assistant/detail', 'AssistantController@detail');    //会员信息
    Route::post('assistant/modify', 'AssistantController@modifyUser');    //会员信息更新
    Route::get('assistant/live', 'AssistantController@live');    //进入直播间
    Route::post('assistant/raffles/create', 'AssistantController@rafflesAdd');    //发布抽奖
    Route::get('assistant/raffles/prizewinner', 'AssistantController@raffleList');    //中奖名单

});

