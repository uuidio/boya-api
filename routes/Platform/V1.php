<?php
/**
 * @Filename Platform/V1.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

Route::namespace('ShopEM\Http\Controllers\Platform\V1')->group(function () {
    Route::post('passport/login', 'PassportController@login')->name('passport.login');  // 平台后台登录

    Route::get('config/addNewConfig', 'ConfigController@addNewConfig');    // 站点挂件配置

    Route::get('wechat-live/pull', 'WechatLiveController@pullList');
    Route::get('wechat-live/lists', 'WechatLiveController@lists');

    Route::get('logistics/logisticsLists', 'LogisticsController@logisticsLists')->name('logistics.logisticsLists'); // 物流列表

});
Route::namespace('ShopEM\Http\Controllers\Platform\V1')->middleware('auth:admin_users','permission')->group(function () {
//     Route::namespace('ShopEM\Http\Controllers\Platform\V1')->group(function () {

    Route::get('index/detail', 'IndexController@detail')->name('index.detail');   //首页数据
    Route::get('index/currentTime', 'IndexController@currentTime')->name('index.currentTime');   //今天实时概况
// Route::namespace('ShopEM\Http\Controllers\Platform\V1')->group(function () {
    Route::get('/passport/logout', 'PassportController@logout')->name('passport.logout');    // 平台后台退出

    Route::get('/member/lists', 'MemberController@lists')->name('member.lists');  // 会员列表
    Route::post('/upload/image', 'UploadController@image')->name('upload.image'); // 上传图片

    /*
     * 文章
     */
    Route::get('article/lists', 'ArticleController@lists')->name('article.lists');    // 文章列表
    Route::get('article/detail/{id?}', 'ArticleController@detail')->name('article.detail');  // 文章详情
    Route::post('article/add', 'ArticleController@store')->name('article.add');    // 添加文章
    Route::post('article/edit', 'ArticleController@update')->name('article.edit');    // 更新文章数据
    Route::get('article/delete/{id?}', 'ArticleController@delete')->name('article.delete'); // 删除文章
    Route::get('article/class/lists', 'ArticleClassController@lists')->name('article.class.lists');    // 文章分类列表
    Route::get('article/class/allClassTree', 'ArticleClassController@allClassTree')->name('article.class.allClassTree');    // 文章分类树
    Route::get('article/class/detail/{id?}', 'ArticleClassController@detail')->name('article.class.detail');  // 文章分类详情
    Route::post('article/class/add', 'ArticleClassController@store')->name('article.class.add');    // 添加文章分类
    Route::post('article/class/edit', 'ArticleClassController@update')->name('article.class.edit');    // 更新文章分类数据
    Route::get('article/class/delete/{id?}', 'ArticleClassController@delete')->name('article.class.delete'); // 删除文章分类
    //自营使用
    Route::get('article/manage/lists', 'ArticleController@manageList')->name('article.manage.lists');    // 文章列表
    Route::post('article/manage/act', 'ArticleController@manageAct')->name('article.manage.act');    // 更新文章数据


    /*
     * Album
     */
    Route::get('/album/pics', 'AlbumController@pics')->name('album.pics');  // 图片相册
    Route::post('album/del-id', 'AlbumController@delById')->name('album.del-id');  // 根据ID删除图片
    Route::post('album/del-url', 'AlbumController@delByUrl')->name('album.del-url');  // 根据url删除图片

   /*
    * 驳回快捷消息
    */
    Route::get('reject/lists', 'RejectMsgController@lists')->name('reject.lists');   // 快捷消息列表
    Route::get('reject/detail/{id?}', 'RejectMsgController@detail')->name('reject.detail');//快捷消息详情
    Route::get('reject/delete/{id?}', 'RejectMsgController@delete')->name('reject.delete');//删除快捷消息
    Route::post('reject/add', 'RejectMsgController@createRejectMsg')->name('reject.add');//添加快捷消息
    Route::post('reject/edit', 'RejectMsgController@update')->name('reject.edit');//更新快捷消息
    /*
     * 商品
     */
    Route::get('goods/lists', 'GoodsController@lists')->name('goods.lists');   // 品牌列表
    Route::post('goods/updateState', 'GoodsController@updateState')->name('goods.updateState'); // 上下架商品

    Route::get('goods/goodsStockLogsList', 'GoodsController@GoodsStockLogsList')->name('goods.goodsStockLogsList'); // 商品库存日志

    /*
    * 商品分类
    */
    Route::get('goodsClass/lists', 'GoodsClassController@lists')->name('goodsClass.lists');    // 分类列表
    Route::get('goodsClass/detail/{id?}', 'GoodsClassController@detail')->name('goodsClass.detail');  // 分类详情
    Route::post('goodsClass/add', 'GoodsClassController@store')->name('goodsClass.add');    // 添加商品分类
    Route::post('goodsClass/edit', 'GoodsClassController@goodsClassUpdate')->name('goodsClass.edit');    // 更新分类数据
    Route::get('goodsClass/delete/{id?}', 'GoodsClassController@delete')->name('goodsClass.delete'); // 删除分类
    Route::get('goods/allClassTree', 'GoodsClassController@allClassTree')->name('goodsClass.allClassTree');   // 商品分类树
    /*
    * 品牌
    */
    Route::get('brand/lists', 'BrandController@lists')->name('brand.lists');   // 品牌列表
    Route::get('brand/detail/{id?}', 'BrandController@detail')->name('brand.detail');  // 品牌详情
    Route::post('brand/add', 'BrandController@store')->name('brand.add');    // 添加品牌
    Route::post('brand/edit', 'BrandController@update')->name('brand.edit');    // 更新品牌
    Route::get('brand/delete/{id?}', 'BrandController@delete')->name('brand.delete'); // 删除品牌

    /*
    * 店铺管理
    */

    Route::get('shop/lists', 'ShopController@lists')->name('shop.lists');  // 店铺列表
    Route::get('shop/list-nopage', 'ShopController@listNoPage')->name('shop.list-nopage');  // 店铺列表（无分页）
    Route::get('shop/detail/{id?}', 'ShopController@detail')->name('shop.detail');  // 店铺详情
    Route::post('shop/add', 'ShopController@createShop')->name('shop.add');    // 添加店铺
    Route::post('shop/edit', 'ShopController@update')->name('shop.edit');    // 更新店铺
    Route::post('shop/update-point', 'ShopController@updatePoint')->name('shop.edit-point');    // 更新积分设置
    Route::get('shop/delete/{id?}', 'ShopController@delete')->name('shop.delete'); // 删除店铺

    Route::get('shop/sellerList', 'ShopSellersController@Lists')->name('shop.sellerList'); // 商家账号(店主列表)
    Route::post('shop/createAccount', 'ShopSellersController@createAccount')->name('shop.createAccount'); // 创建商家账号
    Route::post('shop/sellerUpdate', 'ShopSellersController@sellerResetPwd')->name('shop.sellerUpdate'); // 商家账号修改密码
    Route::post('shop/sellerUpdateType', 'ShopSellersController@sellerResetType')->name('shop.sellerUpdateType'); // 商家账号修改用户角色
    Route::post('shop/switch', 'ShopSellersController@account_switch')->name('shop.switch'); // 商家账户开关


    Route::get('shop/enterApply/{id?}', 'ShopController@enterApply')->name('shop.enterApply');  // 商家提交开店申请
    Route::post('shop/doExamine', 'ShopController@doExamine')->name('shop.doExamine');  // 开店
    Route::post('shop/actShop', 'ShopController@actShop')->name('shop.actShop');  // 关闭或者开启店铺


    Route::get('shopType/lists', 'ShopTypeController@lists')->name('shopType.lists');  // 店铺类型列表
    Route::get('shopType/detail', 'ShopTypeController@detail')->name('shopType.detail');  // 店铺类型详情
    Route::post('shopType/create', 'ShopTypeController@create')->name('shopType.create');    // 添加店铺类型
    Route::post('shopType/update', 'ShopTypeController@update')->name('shopType.update');    // 更新店铺类型
    Route::get('shopType/delete', 'ShopTypeController@delete')->name('shopType.delete'); // 删除店铺类型


    /*
     * 订单
     */
    Route::get('trade/lists', 'TradeController@lists')->name('trade.lists'); // 订单列表
    Route::get('trade/detail', 'TradeController@detail')->name('trade.detail'); // 订单详情
    Route::get('trade/cancelLists', 'TradeController@cancelLists')->name('trade.cancelLists'); // 取消订单列表
    Route::post('trade/tradeCancelCreate', 'TradeController@tradeCancelCreate')->name('trade.tradeCancelCreate'); // 平台直接取消订单
    Route::post('trade/export/filter', 'TradeController@filterExport')->name('trade.export.filter'); // 筛选导出订单
    Route::get('trade/stock/return/list', 'TradeController@getTradeStockReturnLogList')->name('trade.stock-return-list'); // 订单库存回传日志列表
    // Route::post('trade/pick-up', 'TradeController@pickUp'); // 订单提货操作

    /*
     * 售后
     */
    Route::get('trade/afterSalesLists', 'TradeAfterSalesController@Lists')->name('trade.afterSalesLists'); // 售后列表
    Route::get('trade/afterSalesDetail/{id?}', 'TradeAfterSalesController@detailBasic')->name('trade.afterSalesDetail'); // 售后明细
    Route::get('trade/refundsLists', 'TradeAfterRefundController@refundsLists')->name('trade.refundsLists'); // 退款列表
    Route::get('trade/refunds/down', 'TradeAfterRefundController@TradeRefundsDown')->name('trade.refunds.down'); // 退款列表导出
    Route::get('trade/refundsPay', 'TradeAfterRefundController@refundsPay')->name('trade.refundsPay'); // 展示退款信息
    Route::post('trade/dorefund', 'TradeAfterRefundController@dorefund')->name('trade.dorefund'); // 执行退款操作,线上原路返还退款或者线下转款
    Route::get('trade/resetSellerRefund', 'TradeAfterRefundController@ResetSellerRefund')->name('trade.resetSellerRefund'); // 驳回商家审核同意退款项
    Route::get('trade/afterSales/down', 'TradeAfterSalesController@TradeAfterSalesDown')->name('trade.aftersales.down'); // 售后列表导出

    Route::get('trade/onceRefundDetail', 'TradeAfterRefundController@onceRefundDetail')->name('trade.onceRefundDetail'); // 已付款订单详情
    /*
     * 站点挂件配置
     */
    Route::get('siteConfig/items', 'SiteConfigController@items')->name('siteConfig.items');    // 站点挂件配置
    Route::post('siteConfig/add', 'SiteConfigController@store')->name('siteConfig.add');    // 添加挂件配置
    /**
     * 为你推荐
     */
    Route::get('recommend/items', 'RecommendConfigController@items')->name('recommend.config.items');    // 为你推荐配置
    Route::post('recommend/add', 'RecommendConfigController@store')->name('recommend.config.add');    // 添加为你推荐

    /**
     * 首页装修
     */
    Route::get('config/items-index', 'ConfigController@items_index')->name('config.index.items');    // 首页装修
    Route::post('config/add-index', 'ConfigController@store_index')->name('config.index.add');    // 添加首页装修

    /*
     * 站点配置
     */
    Route::get('config/items', 'ConfigController@items')->name('items.items');    // 站点配置
    Route::post('config/add', 'ConfigController@store')->name('items.add');    // 添加配置


    Route::post('logistics/LogisticsAdd', 'LogisticsController@LogisticsAdd')->name('logistics.LogisticsAdd'); // 物流公司添加
    Route::post('logistics/LogisticsEdit', 'LogisticsController@LogisticsEdit')->name('logistics.LogisticsEdit'); // 物流公司编辑
    Route::get('logistics/LogisticsDel/{id?}', 'LogisticsController@LogisticsDel')->name('logistics.LogisticsDel'); // 物流公司删除


    /*
     * 会员
     */
    Route::get('member/lists', 'MemberController@lists')->name('member.lists');   // 会员列表
    Route::get('member/search', 'MemberController@search')->name('member.search');   // 筛选会员列表
    Route::post('member/export', 'MemberController@filterExport')->name('member.export');   // 筛选导出会员

    /*
     * 优惠券
     */
    Route::get('coupon/lists', 'CouponController@lists')->name('coupon.lists');   // 优惠券列表
    Route::post('coupon/save', 'CouponController@saveData')->name('coupon.save');   // 保存优惠券
    Route::get('coupon/detail/{id?}', 'CouponController@detail')->name('coupon.detail');   // 优惠券详情
    Route::get('coupon/delete/{id?}', 'CouponController@delete')->name('coupon.delete');   // 删除优惠券
    Route::post('coupon/push', 'CouponController@pushCoupon')->name('coupon.push');   // 推送优惠券
    Route::post('coupon/check', 'CouponController@check')->name('coupon.check');   // 审核优惠券
    Route::post('coupon/batch-check', 'CouponController@batchCheck')->name('coupon.batch-check');   // 批量审核优惠券
    Route::post('coupon/editName', 'CouponController@editName')->name('coupon.check');   // 重新编辑优惠券名
    Route::get('coupon/offlist', 'CouponController@couponOffList');   // 线下核销列表
    Route::post('coupon/updateStorage', 'CouponController@updateStorage');   // 修改优惠券库存
    Route::post('coupon/distribute', 'CouponController@distributeConpou');   // 派发(上下架)优惠券

    /**
     * 活动
     */
    Route::get('activity/lists', 'ActivityController@lists')->name('activity.lists');   // 活动列表
    Route::post('activity/save', 'ActivityController@saveData')->name('activity.save');   // 保存活动信息
    Route::get('activity/detail/{id?}', 'ActivityController@detail')->name('activity.detail');   // 活动详情详情
    Route::get('activity/delete/{id?}', 'ActivityController@delete')->name('activity.delete');   // 删除活动
    Route::post('activity/check', 'ActivityController@check')->name('activity.check');   // 活动审核
    Route::post('activity/editName', 'ActivityController@editName')->name('activity.editName');   // 重新编辑活动名
    Route::post('activity/stop', 'ActivityController@stop')->name('activity.stop');   // 中止活动

    /*
     * 专题活动
     */
    Route::get('special-activity/lists', 'SpecialActivityController@lists')->name('special-activity.lists'); //专题活动列表
    Route::post('special-activity/save', 'SpecialActivityController@saveData')->name('special-activity.save'); //保存专题活动
    Route::get('special-activity/detail/{id?}', 'SpecialActivityController@detail')->name('special-activity.detail'); //专题活动详情
    Route::get('special-activity/delete/{id?}', 'SpecialActivityController@delete')->name('special-activity.delete'); //删除专题活动
    Route::get('special-activity/apply-list', 'SpecialActivityController@applyLists')->name('special-activity.apply-list'); //专题活动报名列表
    Route::post('special-activity/apply-review', 'SpecialActivityController@review')->name('special-activity.apply-review'); //专题活动报名审核


    Route::post('paycenter/setPayment', 'PaycenterController@editPayment')->name('paycenter.setPayment'); //  设置支付配置
    Route::post('paycenter/getPaymentSetting', 'PaycenterController@getPaymentSetting')->name('paycenter.getPaymentSetting'); // 获取支付的配置字段

    /*
     * 评价
     */
    Route::get('rate/lists', 'RateController@lists')->name('rate.lists'); // 评价列表
    Route::get('rate/detail', 'RateController@detail')->name('rate.detail'); // 评价详情
    Route::get('rate/appeal/lists', 'RateAppealController@lists')->name('rate.appeal.lists'); // 申诉列表
    Route::get('rate/appeal/detail', 'RateAppealController@detail')->name('rate.appeal.detail'); // 申诉详情
    Route::post('rate/appeal/check', 'RateAppealController@check')->name('rate.appeal.check'); // 申诉审批

    /*
     * 物流地区
     */
    Route::get('regions/lists', 'RegionsController@lists')->name('regions.lists');    // 地区列表
    Route::get('regions/detail/{id?}', 'RegionsController@detail')->name('regions.detail');  // 地区详情
    Route::post('regions/add', 'RegionsController@store')->name('regions.add');    // 添加地区
    Route::post('regions/edit', 'RegionsController@update')->name('regions.edit');    // 更新地区数据
    Route::get('regions/delete/{id?}', 'RegionsController@delete')->name('regions.delete'); // 删除地区
    Route::get('regions/allClassTree', 'RegionsController@allClassTree')->name('regions.allClassTree');   // 地区分类树

    /**
     * 热门搜索关键字
     */
    Route::get('goods/hotkeywords/lists', 'GoodsHotkeywordsController@lists')->name('goods.hotkeyword.lists');   // 热门搜索关键字列表
    Route::get('goods/hotkeywords/detail/{id?}', 'GoodsHotkeywordsController@detail')->name('goods.hotkeyword.detail');  // 关键字详情
    Route::post('goods/hotkeywords/add', 'GoodsHotkeywordsController@store')->name('goods.hotkeyword.add');    // 添加关键字
    Route::post('goods/hotkeywords/edit', 'GoodsHotkeywordsController@update')->name('goods.hotkeyword.edit');    // 更新关键字
    Route::get('goods/hotkeywords/delete/{id?}', 'GoodsHotkeywordsController@delete')->name('goods.hotkeyword.delete'); // 删除关键字


    /**
     * 订单支付单关联列表
     */
    Route::get('trade/payment/lists', 'TradePaymentController@lists')->name('trade.payment.lists'); // 订单支付单关联列表
    Route::post('trade/payment/down', 'TradePaymentController@PaymentDown')->name('trade.payment.down'); // 订单支付单关联列表导出


    /**
     * 会员积分管理
     */
    Route::get('member/{user_id?}/point', 'MemberController@detail_point')->name('member.point'); // 会员积分
    Route::post('member/point/change', 'MemberController@changePoint')->name('member.point.change'); // 更改会员积分

    /**
     * 留言
     */
    Route::get('message/list', 'MessageController@lists')->name('message.list'); // 留言列表

    /*
     * 规格管理
     */
    Route::get('goodsSpec/lists', 'SpecController@lists')->name('goodsSpec.lists');   // 规格列表
    Route::get('goodsSpec/detail/{id?}', 'SpecController@detail')->name('goodsSpec.detail');  // 规格详情
    Route::post('goodsSpec/add', 'SpecController@store')->name('goodsSpec.add');    // 添加规格
    Route::post('goodsSpec/edit', 'SpecController@update')->name('goodsSpec.edit');    // 更新规格
    Route::get('goodsSpec/delete/{id?}', 'SpecController@delete')->name('goodsSpec.delete'); // 删除规格


    /*
     * 类型管理
     */
    Route::get('goodsType/lists', 'GoodsTypeController@lists')->name('goodsType.lists');   //  类型列表
    Route::get('goodsType/detail/{id?}', 'GoodsTypeController@detail')->name('goodsType.detail');  //  类型详情
    Route::post('goodsType/add', 'GoodsTypeController@store')->name('goodsType.add');    // 添加类型
    Route::post('goodsType/edit', 'GoodsTypeController@update')->name('goodsType.edit');    // 更新类型
    Route::get('goodsType/delete/{id?}', 'GoodsTypeController@delete')->name('goodsType.delete'); // 删除类型
    Route::get('goodsType/attrShow/{id?}', 'GoodsTypeController@attrShow')->name('goodsType.attrShow'); // 更新属性值展示页面
    Route::post('goodsType/attrEdit', 'GoodsTypeController@attrEdit')->name('goodsType.attrEdit'); // 更新属性值


    /**
     *
     *  秒杀活动
     */
    Route::get('seckill/secKillAppliesLists', 'SecKillController@SecKillAppliesLists')->name('seckill.secKillAppliesLists'); // 秒杀活动列表
    Route::post('seckill/createSeckillApplies', 'SecKillController@CreateSeckillApplies')->name('seckill.createSeckillApplies'); // 添加活动
    Route::post('seckill/updateSeckillApplies', 'SecKillController@UpdateSeckillApplies')->name('seckill.updateSeckillApplies'); // 更新活动
    Route::get('seckill/listDetailSeckillApplies', 'SecKillController@ListDetailSeckillApplies')->name('seckill.listDetailSeckillApplies'); // 平台活动活动详情
    Route::get('seckill/detailSeckillApplies', 'SecKillController@DetailSeckillApplies')->name('seckill.detailSeckillApplies'); // 报名详情
    Route::get('seckill/deleteSeckillApplies', 'SecKillController@DeleteSeckillApplies')->name('seckill.deleteSeckillApplies'); // 删除活动
    Route::get('seckill/deleteForceSeckillApplies', 'SecKillController@DeleteForceSeckillApplies')->name('seckill.deleteForceSeckillApplies'); // 强制删除活动

    Route::get('seckill/secKillRegisterList', 'SecKillController@SecKillRegisterList')->name('seckill.secKillRegisterList'); // 活动报名审核列表
    Route::post('seckill/registerApprove', 'SecKillController@RegisterApprove')->name('seckill.registerApprove'); // 审核报名
    Route::get('seckill/secKillAppliesGoodList', 'SecKillController@SecKillAppliesGoodList')->name('seckill.secKillAppliesGoodList'); // 参加秒杀活动的商品
    Route::get('seckill/shelvesGood', 'SecKillController@ShelvesGood')->name('seckill.shelvesGood'); //将参加秒杀活动的商品审核状态改为下架
    Route::get('seckill/secKillGoodList', 'SecKillController@secKillGoodList')->name('seckill.secKillGoodList'); //活动报名商品,审核用

    /**
     * 直播模块
     */

    Route::get('live/user/list', 'LiveController@userList')->name('platformslive.user.live');    //主播列表
    Route::get('live/platforms/shops', 'LiveController@platformsShops')->name('platformslive.platforms.shops');    //品牌下的所有门店
    Route::post('live/shop/binding', 'LiveController@bindingShop')->name('platformslive.shop.binding');    //主播绑定门店

    Route::post('live/add', 'LiveController@addLive')->name('live.add');    // 添加商户直播间
    Route::post('live/update', 'LiveController@updateLive')->name('live.update');    // 添加商户直播间
    Route::get('live/list', 'LiveController@listLive')->name('live.list');    // 添加商户直播间
    Route::post('live/sensitive', 'LiveController@sensitive')->name('live.sensitive');    // 直播间敏感词
    Route::get('live/sensitiveEdit', 'LiveController@sensitive_edit')->name('live.sensitiveEdit');    // 直播间敏感词获取
    Route::get('live/statistics', 'LiveController@statistics')->name('live.statistics');    // 直播间统计

    Route::get('live/filterExport', 'LiveController@filterExport');    // 直播列表导出
    Route::get('goods/filterExport', 'GoodsController@filterExport');    // 直播列表导出

    Route::post('live/rebroadcast', 'LiveController@rebroadcast')->name('live.rebroadcast');    // 直播间转播授权
    Route::post('live/rebroadcast/cancel', 'LiveController@rebroadcastCancel')->name('live.rebroadcastCancel');    // 直播间转播授权取消
    Route::post('live/notice/add', 'LiveController@noticeAdd')->name('live.noticeAdd');    // 直播设备公告
    Route::post('live/notice/save', 'LiveController@noticeSave')->name('live.noticeSave');    // 直播设备公告
    Route::get('live/notice/list', 'LiveController@noticeList')->name('live.noticeList');    // 直播设备公告


    /**
     * 店铺楼层
     */
    Route::get('shopFloors/lists', 'ShopFloorsController@lists')->name('shopFloors.lists');    // 店铺楼层列表
    Route::get('shopFloors/detail', 'ShopFloorsController@detail')->name('shopFloors.detail');  // 店铺楼层详情
    Route::post('shopFloors/add', 'ShopFloorsController@store')->name('shopFloors.add');    // 添加店铺楼层
    Route::post('shopFloors/edit', 'ShopFloorsController@update')->name('shopFloors.edit');    // 更新店铺楼层
    Route::get('shopFloors/delete', 'ShopFloorsController@delete')->name('shopFloors.delete'); // 删除店铺楼层


    /*
     * 商场店铺分类
     */
    Route::get('shopRelCats/lists', 'ShopRelCatsController@lists')->name('shopRelCats.lists'); // 商场分类列表
    Route::post('shopRelCats/storeCat', 'ShopRelCatsController@storeCat')->name('shopRelCats.storeCat'); // 增加商场分类
    Route::post('shopRelCats/updateCat', 'ShopRelCatsController@updateCat')->name('shopRelCats.updateCat'); // 更新商场分类
    Route::get('shopRelCats/detail', 'ShopRelCatsController@detail')->name('shopRelCats.detail'); // 商场分类详情
    Route::get('shopRelCats/removeCat', 'ShopRelCatsController@delete')->name('shopRelCats.removeCat'); // 删除商场分类
    Route::get('shopRelCats/allShopRelCatsTree', 'ShopRelCatsController@allShopRelCatsTree')->name('shopRelCats.allShopRelCatsTree'); // 商场分类

    /**
     * 报表类数据
     */
    Route::post('stats/analysis', 'StatController@analysis')->name('stats.analysis'); //经营概况
    Route::post('stats/userAnalysis', 'StatController@userAnalysis')->name('stats.userAnalysis'); //会员排行
    Route::post('stats/tradeAnalysis', 'StatController@tradeAnalysis')->name('stats.tradeAnalysis'); //交易数据统计
    Route::post('stats/ajaxTimeType', 'StatController@timeType')->name('stats.ajaxTimeType'); //异步加载时间跨度选择器
    Route::post('stats/storeAnalysis', 'StatController@storeListAnalysis')->name('stats.storeAnalysis'); //店铺数据统计
    Route::post('stats/goodsAnalysis', 'StatController@goodsListAnalysis')->name('stats.goodsAnalysis'); //商品数据统计


    //积分专区
    Route::get('pointGoods/lists', 'PointActivityController@lists')->name('pointGoods.lists'); // 积分专区商品列表
    Route::get('pointGoods/detail/{id?}', 'PointActivityController@detail')->name('pointGoods.detail'); // 积分专区商品详情
    Route::post('pointGoods/create', 'PointActivityController@saveData')->name('pointGoods.create'); // 添加积分专区商品
    Route::get('pointGoods/delete', 'PointActivityController@delete')->name('pointGoods.delete'); // 删除积分专区商品
    Route::post('pointGoods/update', 'PointActivityController@update')->name('pointGoods.update'); // 更新积分专区商品

    //积分转牛币
    Route::get('cowcoinToPoint/lists', 'CowCoinActivityController@lists')->name('cowcoinToPoint.lists'); // 积分转牛币列表
    // Route::get('cowcoinToPoint/search', 'CowCoinActivityController@search')->name('cowcoinToPoint.search'); // 积分转牛币筛选列表
    Route::post('cowcoinToPoint/cowCoinLogDown', 'CowCoinActivityController@cowCoinLogDown')->name('cowcoinToPoint.cowCoinLogDown'); // 积分转牛币列表导出


    //结算报表
    Route::get('tradeSettlement/tradeDayDetailLists', 'TradeSettlementController@TradeDayDetailLists')->name('tradeSettlement.tradeDayDetailLists'); // 日结算明细列表
    Route::get('tradeSettlement/tradeDayLists', 'TradeSettlementController@TradeDayLists')->name('tradeSettlement.tradeDayLists'); // 日结列表
    Route::get('tradeSettlement/tradeDayDetailGoodsLists', 'TradeSettlementController@TradeDayDetailGoodsLists')->name('tradeSettlement.tradeDayDetailGoodsLists'); // 日结算商品明细
    Route::get('tradeSettlement/tradeMonthLists', 'TradeSettlementController@TradeMonthLists')->name('tradeSettlement.tradeMonthLists'); // 月结列表
    Route::post('tradeSettlement/settleMentReview', 'TradeSettlementController@SettleMentReview')->name('tradeSettlement.settleMentReview'); // 确认结算
    Route::get('tradeSettlement/tradeDayDetailListsDown', 'TradeSettlementController@TradeDayDetailListsDown')->name('tradeSettlement.tradeDayDetailListsDown'); // 日结算明细下载
    Route::get('tradeSettlement/tradeDayListsDown', 'TradeSettlementController@TradeDayListsDown')->name('tradeSettlement.tradeDayListsDown'); // 日结下载
    Route::get('tradeSettlement/tradeMonthListsDown', 'TradeSettlementController@TradeMonthListsDown')->name('tradeSettlement.tradeMonthListsDown'); // 月结下载



    /*
     * 角色权限菜单管理
     */
    Route::get('permission/manage', 'PermissionMenuController@lists')->name('permission.manage');    // 权限管理
    Route::get('permission/platformRoutes', 'PermissionMenuController@platformRoutes')->name('permission.platform.routes');    // 平台后台路由
    Route::post('permission/createMenu', 'PermissionMenuController@create')->name('permission.create.menu');    // 新增后台管理菜单
    Route::post('permission/updateMenu', 'PermissionMenuController@update')->name('permission.update.menu');    // 更新后台管理菜单
    Route::get('permission/deleteMenu', 'PermissionMenuController@delete')->name('permission.delete.menu');    // 删除后台管理菜单
    Route::get('permission/menuDetail', 'PermissionMenuController@detail')->name('permission.menu.detail');    // 后台管理菜单详情
    Route::get('permission/menuLists', 'PermissionMenuController@lists')->name('permission.menu.lists');    // 后台管理菜单列表

    /*
     * 角色管理
     */
    Route::get('role/lists', 'RoleController@lists')->name('role.lists');    // 角色列表
    Route::post('role/create', 'RoleController@create')->name('role.create');    // 新增角色
    Route::post('role/update', 'RoleController@update')->name('role.update');    // 更新角色
    Route::get('role/delete', 'RoleController@delete')->name('role.delete');    // 删除角色
    Route::get('role/detail', 'RoleController@detail')->name('role.detail');    // 角色详情

    /*
     * 后台管理员
     */
    Route::get('admin/lists', 'AdminController@lists')->name('admin.lists');    // 管理员列表
    Route::post('admin/create', 'AdminController@create')->name('admin.create');    // 新增管理员
    Route::post('admin/update', 'AdminController@update')->name('admin.update');    // 更新管理员
    Route::get('admin/delete', 'AdminController@delete')->name('admin.delete');    // 删除管理员
    Route::get('admin/detail', 'AdminController@detail')->name('admin.detail');    // 管理员详情


    /**
     * 平台系统日志
     */
    Route::get('adminLogs/lists', 'AdminLogsController@lists')->name('adminLogs.detail');    // 系统日志列表
    Route::get('adminLogs/delete', 'AdminLogsController@delete')->name('adminLogs.delete');    // 系统日志删除

    Route::get('trade/point/lists', 'TradePointController@lists')->name('trade.point.lists'); // 订单积分列表
    Route::get('trade/point/down', 'TradePointController@TradePointDown')->name('trade.point.down'); // 订单积分列表导出




    /**
     * 新版挂件
     */
    Route::get('siteConfig/items_v1', 'SiteConfigController@items_v1')->name('siteConfig.items_v1');    // 站点挂件配置
    Route::post('siteConfig/add_v1', 'SiteConfigController@store_v1')->name('siteConfig.add_v1');    // 添加挂件配置
    Route::get('siteConfig/show_v1', 'SiteConfigController@show_v1')->name('siteConfig.show_v1');    // 展示挂件配置
    Route::get('siteConfig/custom-list', 'SiteConfigController@customActivityList')->name('siteConfig.custom-list'); //自定义活动列表
    Route::get('siteConfig/custom-delete', 'SiteConfigController@customActivityDelete')->name('siteConfig.custom-delete'); //自定义活动列表

      /*
     * 积分商品分类
     */
     Route::get('pointGoodClass/lists', 'PointGoodsClassController@lists')->name('pointGoodClass.lists');//积分商品分类列表
     Route::post('pointGoodClass/create', 'PointGoodsClassController@create')->name('pointGoodClass.create');//新增积分商品分类
     Route::post('pointGoodClass/update', 'PointGoodsClassController@update')->name('pointGoodClass.update');//更新积分商品分类
     Route::get('pointGoodClass/delete', 'PointGoodsClassController@delete')->name('pointGoodClass.delete');//删除分类
     Route::get('pointGoodClass/detail', 'PointGoodsClassController@detail')->name('pointGoodClass.detail');//分类详情



     /**
      * 会员卡
      */
    Route::get('userCard/lists', 'UserCardController@lists')->name('usercard.lists');//会员卡列表
    Route::post('userCard/save', 'UserCardController@save')->name('usercard.save');//新增会员卡
    Route::get('userCard/detail/{id?}', 'UserCardController@detail')->name('usercard.detail');//会员卡详情

     /**
      * 规则
      */
     Route::get('rule/lists', 'RuleController@lists')->name('rule.lists');//规则列表
     Route::post('rule/save', 'RuleController@save')->name('rule.save');//新增规则
     Route::post('rule/update', 'RuleController@update')->name('rule.update');//更新规则
     Route::get('rule/detail/{id?}', 'RuleController@detail')->name('rule.detail');//规则详情
     Route::get('rule/delete', 'RuleController@delete')->name('rule.delete');//删除规则
     Route::get('rule/ruleType', 'RuleController@ruleType')->name('rule.ruleType');//规则类型


     /**
      * 会员活动
      */
     Route::get('member-activity/lists', 'MemberActivityController@lists')->name('member-activity.lists');//活动列表
     Route::post('member-activity/save', 'MemberActivityController@save')->name('member-activity.save');//保存活动
     Route::post('member-activity/update', 'MemberActivityController@update')->name('member-activity.update');//更新活动
     Route::get('member-activity/detail/{id?}', 'MemberActivityController@detail')->name('member-activity.detail');//规则详情
     Route::get('member-activity/delete', 'MemberActivityController@delete')->name('member-activity.delete');//删除活动

     /**
      * 会员活动场次
      */
     Route::post('member-activity-sku/save', 'MemberActivitySkuController@save')->name('member-activity-sku.save');//增加场次
     Route::get('member-activity-sku/detail/{id?}', 'MemberActivitySkuController@detail')->name('member-activity-sku.detail');//场次详情
     Route::get('member-activity-sku/delete', 'MemberActivitySkuController@delete')->name('member-activity-sku.delete');//删除场次
     Route::post('member-activity-sku/update', 'MemberActivitySkuController@update')->name('member-activity-sku.update');//更新场次

     /**
      * 会员报名
      */
     Route::get('member-activity-apply/lists', 'MemberActivityApplyController@lists')->name('member-activity-apply.lists');//报名列表
     Route::get('member-activity-apply/verify', 'MemberActivityApplyController@verify')->name('member-activity-apply.verify');//报名审核

    //-----------------抽奖模块start--------------

    /**
     * 抽奖活动
     */
    Route::get('lottery/activity/list','LotteryController@activityLotteryList')->name('lottery.activity.list'); // 抽奖活动列表
    Route::post('lottery/activity/create','LotteryController@activityLotteryCreate')->name('lottery.activity.create'); // 抽奖活动添加
    Route::get('lottery/activity/delete/{id?}','LotteryController@activityLotteryDelete')->name('lottery.activity.delete'); // 抽奖活动删除
    Route::post('lottery/activity/update','LotteryController@activityLotteryUpdate')->name('lottery.activity.update'); // 抽奖活动修改
    Route::get('lottery/activity/detail','LotteryController@activityLotteryDetail')->name('lottery.activity.detail'); //  抽奖活动详情
    Route::post('lottery/activity/desc','LotteryController@activityLotteryDesc')->name('lottery.activity.setDesc'); //  设置抽奖规则说明
    Route::post('lottery/activity/qrcode','LotteryController@activityQrCode')->name('lottery.activity.qrcode'); // 生成抽奖活动二维码

    Route::get('lottery/selectStatus','LotteryController@selectStatus')->name('lottery.selectStatus'); // 查看抽奖活动状态
    Route::post('lottery/setStatus','LotteryController@setStatus')->name('lottery.setStatus'); // 修改抽奖活动状态

    /**
     * 奖项信息
     */
    Route::get('lottery/list','LotteryController@list')->name('lottery.list'); // 奖项列表
    Route::post('lottery/create','LotteryController@create')->name('lottery.create'); // 奖项添加
    Route::get('lottery/delete/{id?}','LotteryController@delete')->name('lottery.delete'); // 奖项删除
    Route::post('lottery/update','LotteryController@update')->name('lottery.update'); // 奖项修改
    Route::get('lottery/detail','LotteryController@detail')->name('lottery.detail'); // 奖项详情
    Route::get('lottery/reissued','LotteryController@reissued')->name('lottery.reissued'); // 抽奖奖品补发

    Route::post('lottery/createWxMiniQr','LotteryController@ActivityLotteryCreateWxMiniQr')->name('lottery.createWxMiniQr'); // 抽奖生成活动二维码

    /**
     * 抽奖记录信息
     */
    Route::get('lottery/record/list','LotteryRecordController@list')->name('lottery.record.list'); // 抽奖记录列表
    Route::get('lottery/record/listDown','LotteryRecordController@ListDown')->name('lottery.record.listDown'); // 抽奖记录数据下载

    Route::get('member/ticket/list','UserTicketController@lists')->name('member.ticket.list'); // 会员票券列表
    Route::post('member/ticket/reset','UserTicketController@ResetTicketStart')->name('member.ticket.reset'); // 修改会员票券状态

    /*
   * 实物奖品相关
   */
    Route::get('reward/activitiesRewardGoodsList','ActivitiesRewardController@ActivitiesRewardGoodsList')->name('reward.activitiesRewardGoodsList'); // 活动奖品列表
    Route::post('reward/activitiesRewardGoodsCreate','ActivitiesRewardController@ActivitiesRewardGoodsCreate')->name('reward.activitiesRewardGoodsCreate'); // 活动实物奖品添加
    Route::post('reward/activitiesRewardGoodsUpdate','ActivitiesRewardController@ActivitiesRewardGoodsUpdate')->name('reward.activitiesRewardGoodsUpdate'); // 活动实物奖品编辑
    Route::get('reward/activitiesRewardGoodsDetail','ActivitiesRewardController@ActivitiesRewardGoodsDetail')->name('reward.activitiesRewardGoodsDetail'); // 实物奖品详情
    Route::get('reward/activitiesRewardGoodsDelete','ActivitiesRewardController@ActivitiesRewardGoodsDelete')->name('reward.activitiesRewardGoodsDelete'); // 活动实物奖品删除

    Route::get('reward/activitiesRewardList','ActivitiesRewardController@ActivitiesRewardList')->name('reward.activitiesRewardList'); // 活动关联的奖品列表
    Route::get('reward/activitiesRewardCreate','ActivitiesRewardController@ActivitiesRewardCreate')->name('reward.activitiesRewardCreate'); // 活动关联实物奖品配置添加
    Route::get('reward/activitiesRewardUpdate','ActivitiesRewardController@ActivitiesRewardUpdate')->name('reward.activitiesRewardUpdate'); // 活动关联实物奖品配置修改
    Route::get('reward/activitiesRewardDetail','ActivitiesRewardController@ActivitiesRewardDetail')->name('reward.activitiesRewardDetail'); // 活动关联实物奖品配置详情
    Route::get('reward/activitiesRewardDelete','ActivitiesRewardController@ActivitiesRewardDelete')->name('reward.activitiesRewardDelete'); // 活动关联实物奖品配置删除


    Route::get('reward/activitiesRewardsSendLogs','ActivitiesRewardController@ActivitiesRewardsSendLogs')->name('reward.activitiesRewardsSendLogs'); // 获奖会员列表
    Route::get('reward/activitiesRewardsSendLogsDown','ActivitiesRewardController@ActivitiesRewardsSendLogsDown')->name('reward.activitiesRewardsSendLogsDown'); // 获奖会员列表数据下载
    Route::post('reward/deliveryTrade', 'ActivitiesRewardController@DeliveryTrade')->name('reward.deliveryTrade');; // 实物奖品发货
    Route::post('reward/pickUp', 'ActivitiesRewardController@pickUp')->name('reward.pickUp');; // 提货操作
    Route::get('coupon/luck-draw-coupon', 'CouponController@luckDrawCoupon')->name('coupon.luckDraw');   // 抽奖优惠券列表

    //----------------抽奖模块模块end----------------

    //-----------------自助积分模块start--------------
    Route::get('self-help-integral/lists','IntegralBySelfController@lists')->name('self.lists');     //查看申请列表
    Route::get('self-help-integral/detail/{id?}','IntegralBySelfController@detail')->name('self.detail');  //积分详情
    Route::post('self-help-integral/submit','IntegralBySelfController@submit')->name('self.submit');  //提交审核
    Route::post('self-help-integral/examine','IntegralBySelfController@examine')->name('self.examine');  //直接驳回
    //-----------------自助积分模块end----------------


    /**
     * YITIAN CRM
     */
    Route::get('crm/store/lists','CrmDataController@storeList')->name('crm.store.lists');     //crm 店铺列表


    //-----------------积分订单模块start--------------
    Route::get('integral-trade/lists','TradeController@integralLists')->name('integral.lists');     //积分订单列表
    Route::post('integral-trade/export','TradeController@integralFilterExport')->name('integral.filterExport');     //积分订单导出

    //-----------------积分订单模块end----------------

    //-----------------优惠券兑换汇总列表模块start--------------
    Route::get('coupon/use-list', 'CouponController@couponUseList')->name('coupon.couponUseList');   // 优惠券兑换汇总列表
    Route::post('coupon/use-export','CouponController@couponUseExport')->name('coupon.filterExport');     //优惠券核销导出

    //-----------------优惠券兑换汇总列表模块end----------------

    /**
     * 后台审核推广员模块start
     */
    Route::get('applyPromoter/lists', 'PromoterController@lists')->name('applyPromoter.lists'); // 申请的列表
    Route::get('applyPromoter/detail/{id?}', 'PromoterController@detail')->name('applyPromoter.detail');// 申请的详情
    Route::post('applyPromoter/examine', 'PromoterController@examine')->name('applyPromoter.examine'); // 审核申请

    Route::get('applyPromoter/goodsSpreadLists', 'PromoterController@GoodsSpreadLists')->name('applyPromoter.goodsSpreadLists'); // 审核申请

    Route::post('applyPromoter/setDepartment', 'PromoterController@SetDepartment')->name('applyPromoter.setDepartment'); // 设置部门
    Route::get('applyPromoter/relatedLogsList', 'PromoterController@RelatedLogsList')->name('applyPromoter.relatedLogsList'); // 获取所有推物信息列表

    Route::get('shopAttr/lists', 'ShopAttrController@lists')->name('shopAttr.lists'); //查看店铺配置
    Route::post('shopAttr/deploy', 'ShopAttrController@deploy')->name('shopAttr.deploy'); //配置店铺
    Route::post('shopAttr/controlPromo', 'ShopAttrController@controlPromo')->name('shopAttr.controlPromo'); //一键控制店铺

    Route::get('userDeposits/userDepositCashesList', 'UserDepositsController@UserDepositCashesList')->name('userDeposits.userDepositCashesList'); // 会员申请提现列表
    Route::get('userDeposits/getUserApplyDetail', 'UserDepositsController@GetUserApplyDetail')->name('userDeposits.getUserApplyDetail'); // 会员提现详情
    Route::post('userDeposits/examine', 'UserDepositsController@Examine')->name('userDeposits.examine'); // 审核
    Route::get('userDeposits/tradeEstimatesLists', 'UserDepositsController@TradeEstimatesLists')->name('userDeposits.tradeEstimatesLists'); // 会员推广订单列表(预估收益)
    Route::get('userDeposits/tradeRewardsLists', 'UserDepositsController@TradeRewardsLists')->name('userDeposits.TradeRewardsLists'); // 会员推广订单列表(实际收益收益)
    Route::get('userDeposits/userDepositLogsLists', 'UserDepositsController@UserDepositLogsLists')->name('userDeposits.userDepositLogsLists'); // 会员收入日志
    Route::get('userDeposits/userDepositLists', 'UserDepositsController@UserDepositLists')->name('userDeposits.userDepositLists'); // 会员账户列表
    Route::get('userDeposits/userDepositDetail', 'UserDepositsController@UserDepositDetail')->name('userDeposits.userDepositDetail'); // 会员账户明细
    Route::post('/member/becomePromoter', 'MemberController@becomePromoter')->name('member.becomePromoter');  // 后台授权会员成为推广员


    Route::post('partners/setPartners', 'PromoterController@SetPartners')->name('partners.setPartners'); // 直接授权分销(身份1-推广员,2-小店,3-分销商,4-经销商)
    Route::get('partners/setPartnersLog', 'PromoterController@SetPartnersLog')->name('partners.setPartnersLog'); // 会员账户明细
    Route::post('partners/setPartnersRelated', 'PromoterController@SetPartnersRelated')->name('partners.setPartnersRelated'); // 改合伙人关联
    Route::post('partners/changePartnersRelated', 'PromoterController@ChangePartnersRelated')->name('partners.changePartnersRelated'); // 修改某个会员的上下级关系
    Route::get('partners/getUserInfo', 'PromoterController@GetUserInfo')->name('partners.getUserInfo'); // 会员信息(包含上下级)
    Route::get('partners/unbindRelated', 'PromoterController@UnbindRelated')->name('partners.unbindRelated'); // 推广员解绑小店



    Route::get('ranking/userRewardRankingList', 'UserDepositsController@UserRewardRankingList')->name('Ranking.userRewardRankingList'); // 分销个人销售排行榜
    Route::get('ranking/userRewardRankingListDown', 'UserDepositsController@UserRewardRankingListDown')->name('Ranking.userRewardRankingListDown'); // 分销个人销售排行榜

    Route::get('ranking/promoterLists', 'UserDepositsController@PromoterLists')->name('Ranking.promoterLists'); // 会员佣金统计汇总表
    Route::get('ranking/promoterListsDown', 'UserDepositsController@PromoterListsDown')->name('Ranking.promoterListsDown'); // 会员佣金统计汇总表下载
    Route::get('ranking/groupCollectLists', 'UserDepositsController@GroupCollectLists')->name('Ranking.groupCollectLists'); // 分销团队-销售排行榜-汇总表

    Route::get('ranking/partnerLists', 'UserDepositsController@PartnerLists')->name('Ranking.partnerLists'); // 分销团队-销售排行榜-汇总表
    Route::get('ranking/promoterShopLists', 'UserDepositsController@PromoterShopLists')->name('Ranking.promoterShopLists'); // 小店佣金统计汇总表
    Route::get('ranking/promoterShopListsDown', 'UserDepositsController@PromoterShopListsDown')->name('Ranking.promoterShopListsDown'); // 小店佣金统计汇总下载

    /**-------后台审核推广员模块end------*/

    //-----------------退货模块start--------------
    Route::get('trade/refund-goods-lists', 'TradeAfterSalesController@refundGoodsLists')->name('trade.refund.goods'); // 列表
    Route::post('trade/refund-goods-down', 'TradeAfterSalesController@refundGoodsDown')->name('trade.refund.goods.down'); // 列表导出
    //-----------------退货模块end----------------

    //-----------------确认收货报表模块start--------------
    Route::get('trade/confirm-order-lists', 'TradeController@confirmOrderLists')->name('trade.refund.goods'); // 列表
    Route::post('trade/confirm-order-down', 'TradeController@confirmOrdersDown')->name('trade.refund.goods.down'); // 列表导出
    //-----------------确认收货报表模块end----------------


    //-----------------成本报表模块模块start--------------
    Route::get('trade/goods-cost-lists', 'TradeController@GoodsCostLists')->name('trade.goods.cost'); // 列表
    Route::post('trade/goods-cost-down', 'TradeController@GoodsCostDown')->name('trade.goods.cost.down'); // 列表导出
    //-----------------成本报表模块模块end----------------


    //-----------------店铺列表start--------------
    Route::post('shop/export-list', 'ShopController@ExportList')->name('shop.export.list');  // 店铺列表导出
    //-----------------店铺列表end----------------

    //-----------------日销售报表模块start--------------
    Route::get('trade/daily-sales-list', 'TradeController@DailySalesList')->name('daily.sales.list');  // 销售日报列表
    Route::post('trade/daily-sales-down', 'TradeController@DailySalesDown')->name('daily.sales.down');  // 销售日报列表导出
    //-----------------日销售报表模块end----------------

    //-----------------商品销售报表模块start--------------
    Route::get('trade/good-sale-list', 'TradeController@GoodSaleList')->name('good.sale.list');  // 商品统计列表
    Route::post('trade/good-sale-down', 'TradeController@GoodSaleDown')->name('good.sale.down');  // 商品统计列表导出
    //-----------------商品销售报表模块end----------------

    /**
     * 会员权益相关
     */
    #新会员赠送积分
    Route::get('benefits/detail', 'MemberBenefitsController@detail')->name('benefits.detail');
    Route::post('benefits/registerPoint', 'MemberBenefitsController@registerPoint')->name('benefits.register.point');

    //-----------------商品模块start--------------
    Route::post('goods/down', 'GoodsController@goodDown')->name('goods.down');   // 导出
    //-----------------商品模块end----------------

    //-----------------新导出模块start--------------
    Route::post('member/userAccountDown', 'MemberController@userAccountDown')->name('new.member.userAccountDown');  //会员信息导出
    Route::post('trade/new-export-filter', 'TradeController@newTradeOrder')->name('new.trade.export.filter'); // 筛选导出订单
    Route::post('trade/new-payment-down', 'TradePaymentController@newPaymentDown')->name('new.trade.payment.down'); // 订单支付单关联列表导出
    Route::post('trade/new-goods-cost-down', 'TradeController@newGoodsCostDown')->name('trade.new.goods.cost.down'); // 成本结算列表导出
    /**
     * 导出列表
     */
    Route::get('downloadService/downLoadList', 'DownloadServiceController@DownLoadList')->name('downloadService.downLoadList'); // 导出下载列表
    Route::get('downloadService/delete', 'DownloadServiceController@Delete')->name('downloadService.delete'); // 删除导出记录
    //-----------------新导出模块end----------------

});
