<?php
/**
 * @Filename Seller/V1.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

Route::namespace('ShopEM\Http\Controllers\Seller\V1')->group(function () {
    Route::post('passport/login', 'PassportController@login');  // 商家后台登录

    Route::post('passport/doRegister', 'PassportController@doRegister');  // 商家注册

    Route::get('shop/logisticsLists', 'ShopController@logisticsLists'); // 物流列表

    /*
     * 地区
     */
    Route::get('regions/allClassTree', 'RegionsController@allClassTree'); // 地区

    Route::get('index/test', 'CouponController@couponOffDetail');
});

Route::namespace('ShopEM\Http\Controllers\Seller\V1')->middleware('auth:seller_users')->group(function () {

    Route::get('index/detail', 'IndexController@detail');   //首页数据
    Route::get('index/currentTime', 'IndexController@currentTime');   //今天实时概况

    Route::get('/passport/logout', 'PassportController@logout');    // 退出
    Route::post('passport/resetPwd', 'PassportController@resetPwd');    // 修改密码

    /**
     * 微信
     */
    Route::post('wechat/jsapiconfig', 'WechatController@getApiSdk'); //获取微信jsapi配置
    Route::post('upload/wechatImage', 'UploadController@wechatImage')->name('upload.wechatImage'); // 上传图片

    /*
     * 直播
     */
    Route::post('live/anchor/add', 'LiveController@addAnchor');    // 添加主播
    Route::get('live/anchor/list', 'LiveController@listAnchor');    // 主播列表
    Route::post('live/anchor/passwordUpdate', 'LiveController@anchorPassword');    // 主播密码更新
    Route::post('live/assistant/add', 'LiveController@addAssistant');    // 添加助理
    Route::get('live/assistant/list', 'LiveController@listAssistant');    // 助理列表
    Route::post('live/assistant/passwordUpdate', 'LiveController@assistantPassword');    // 助理列表
    Route::get('live/statistics', 'LiveController@statistics');    // 主播统计
    Route::get('live/rebroadcast/list', 'LiveController@rebroadcastList');    // 转播列表
    Route::post('live/rebroadcast/status', 'LiveController@rebroadcastStatus');    // 转播状态操作

    /*
     * 文章
     */
    Route::get('article/lists', 'ShopArticleController@lists');    // 文章列表
    Route::get('article/detail/{id?}', 'ShopArticleController@detail');  // 文章详情
    Route::post('article/add', 'ShopArticleController@store');    // 添加文章
    Route::post('article/edit', 'ShopArticleController@update');    // 更新文章数据
    Route::get('article/delete/{id?}', 'ShopArticleController@delete'); // 删除文章
    Route::get('article/class/lists', 'ShopArticleClassController@lists');    // 文章分类列表
    Route::get('article/class/allClassTree', 'ShopArticleClassController@allClassTree');    // 文章分类树
    Route::get('article/class/detail/{id?}', 'ShopArticleClassController@detail');  // 文章分类详情
    Route::post('article/class/add', 'ShopArticleClassController@store');    // 添加文章分类
    Route::post('article/class/edit', 'ShopArticleClassController@update');    // 更新文章分类数据
    Route::get('article/class/delete/{id?}', 'ShopArticleClassController@delete'); // 删除文章分类

    /*
     * Album
     */
    Route::get('album/pics', 'AlbumController@pics');  // 图片相册
    Route::post('upload/image', 'UploadController@image'); // 上传图片
    Route::post('album/del-id', 'AlbumController@delById');  // 根据ID删除图片
    Route::post('album/del-url', 'AlbumController@delByUrl');  // 根据url删除图片

    /*
     * 商品
     */
    Route::get('goods/lists', 'GoodsController@lists'); // 商品列表
    Route::get('goods/detail/{id?}', 'GoodsController@detail'); // 商品详情
    Route::post('goods/add', 'GoodsController@store');  // 发布商品
    Route::post('goods/edit', 'GoodsController@update');    // 编辑商品
    Route::get('goods/delete/{id?}', 'GoodsController@delete'); // 删除商品(软删除)
    Route::get('goods/recycle/lists', 'GoodsController@recycleList'); // 商品回收站列表
    Route::post('goods/recycle/restore', 'GoodsController@restore'); // 恢复回收站商品
    Route::post('goods/recycle/delete', 'GoodsController@recycleDel'); // 永久删除回收站商品
    Route::post('goods/updateState', 'GoodsController@updateState'); // 上下架商品
    Route::get('goods/source/lists', 'GoodsController@getSourceList'); // 获取来源列表
    Route::post('goods/showPrice', 'GoodsController@showPrice'); // 展示、隐藏市场价
    Route::get('goods/getGoodSku/{id?}', 'GoodsController@getGoodSku'); // 获取商品sku信息
    Route::post('goods/quickUpdate', 'GoodsController@quickUpdate'); // 获取商品sku信息



    Route::get('goods/getAttr/{id?}', 'GoodsController@getAttr'); // 根据类型获得规格、类型、属性信息(废弃)
    Route::get('goods/getGoodsSpec', 'GoodsController@getGoodsSpec'); // 给商家添加编辑商品列出规格


    //废弃
    Route::post('goods/goodsSpecValue', 'GoodsController@GoodsSpecValueStore'); // 新增规格值
    Route::post('goods/goodsSpecValueEdit', 'GoodsController@GoodsSpecValueEdit'); // 规格值编辑
    Route::post('goods/getGoodsSpecValue', 'GoodsController@getGoodsSpecValue'); // 获取规格值数据
    //废弃

    // goods base data
    Route::get('goods/allClassTree', 'GoodsController@allClassTree');   // 商品分类树
    Route::get('goods/allBrands', 'GoodsController@allBrands'); // 全部品牌

    /*
     * 店铺
     */
    Route::get('shop/detail', 'ShopController@detail'); // 店铺详情
    Route::post('shop/edit', 'ShopController@update'); // 更新店铺
    Route::get('ziti/lists', 'ShopZitiController@lists'); // 店铺自提地址列表
    Route::post('ziti/store', 'ShopZitiController@store'); // 新增自提地址
    Route::post('ziti/edit', 'ShopZitiController@edit'); // 修改自提地址
    Route::post('ziti/del', 'ShopZitiController@del'); // 删除自提地址


    /*
     * 入驻流程
     */
    Route::post('shop/enterApply', 'ShopController@enterApply');  // 商家提交开店申请


    /*
     * 订单
     */
    Route::get('trade/lists', 'TradeController@lists'); // 订单列表
    Route::get('trade/detail', 'TradeController@detail'); // 订单详情
    Route::post('trade/deliveryTrade', 'TradeController@deliveryTrade'); // 订单发货
    Route::post('trade/pick-up', 'TradeController@pickUp'); // 订单提货操作
    Route::post('trade/pick-up-qrcode', 'TradeController@qrcodePickUp'); // 二维码提货操作
    Route::post('trade/pick-info-qrcode', 'TradeController@qrcodePickInfo'); // 二维码提货操作
    Route::get('trade/pick-up-list', 'TradeController@pickUpList'); // 提货列表
    Route::get('trade/cancelLists', 'TradeController@cancelLists'); // 取消订单列表
    Route::post('trade/export/filter', 'TradeController@filterExport'); // 筛选导出订单
    Route::post('trade/shopRemarks', 'TradeController@shopRemarks'); // 保存商家备注
    Route::post('trade/repush', 'TradeController@repush'); // 手动推单
    Route::get('trade/ship-info', 'TradeController@getShipInfo'); // 订单物流信息
    Route::post('trade/addRemark', 'TradeController@addRemark'); //  订单追加备注



    Route::get('trade/getLogisticsInfo', 'TradeController@GetLogisticsInfo'); // 根据订单获取物流轨迹

    /*
     * 售后
     */
    Route::post('trade/tradeCancelCreate', 'TradeController@tradeCancelCreate'); // 商家直接取消订单
    Route::post('trade/tradeCanceldetail', 'TradeController@tradeCanceldetail'); // 取消订单详情

    Route::post('trade/cancelShopReply', 'TradeController@cancelShopReply'); // 会员取消,商家审核取消订单
    Route::get('trade/afterSales/detail', 'TradeAfterSalesController@detail'); // 商家退换货详情
    Route::get('trade/afterSales/list', 'TradeAfterSalesController@Lists'); // 商家售后列表
    Route::post('trade/afterSalesVerification', 'TradeAfterSalesController@verification'); // 商家退换货审核
    Route::post('trade/afterSales/sendConfirm', 'TradeAfterSalesController@sendConfirm'); // 填写换货重新发货物流信息
    Route::get('trade/afterSales/down', 'TradeAfterSalesController@TradeAfterSalesDown'); // 售后列表导出
    Route::post('trade/afterSales/again-examine', 'TradeAfterSalesController@againExamine'); // 审核订单多次开启多次售后
    Route::post('trade/afterSales/update-aftersale-trace', 'TradeAfterSalesController@updateAftersaleTrace'); // 添加订单追踪备注
    Route::get('trade/afterSales/get-aftersale-trace', 'TradeAfterSalesController@getAftersaleTrace'); // 获取订单追踪信息

    /*
     * 优惠券
     */
    Route::get('coupon/lists', 'CouponController@lists');   // 优惠券列表
    Route::post('coupon/save', 'CouponController@saveData');   // 保存优惠券
    Route::get('coupon/detail/{id?}', 'CouponController@detail');   // 优惠券详情
    Route::get('coupon/delete/{id?}', 'CouponController@delete');   // 删除优惠券
    Route::post('coupon/send', 'CouponController@send');   // 发放优惠券
    Route::get('coupon/off-line/list', 'CouponController@getOffLineCouponStock');   // 获取线下优惠券库存列表

    Route::get('coupon/offlist', 'CouponController@couponOffList');   // 线下核销列表
    Route::post('coupon/writeOff', 'CouponController@writeOff');   // 线下核销
    Route::post('coupon/off-take/find', 'CouponController@takeFindCoupon');   // 输入方式查询
    Route::post('coupon/off-qrcode/find', 'CouponController@qrcodeFindCoupon');   // 二维码方式查询
    Route::post('coupon/distribute', 'CouponController@distributeConpou');   // 派发(上下架)优惠券
    Route::post('coupon/updateStorage', 'CouponController@updateStorage');   // 修改优惠券库存



    /*
     * 活动
     */
    Route::get('activity/lists', 'ActivityController@lists');   // 活动列表
    Route::post('activity/save', 'ActivityController@saveData');   // 保存活动
    Route::get('activity/detail/{id?}', 'ActivityController@detail');   // 活动详情
    Route::get('activity/delete/{id?}', 'ActivityController@delete');   // 删除活动
    Route::get('activity/stop/{id?}', 'ActivityController@stop');   // 停止活动

    /*
     * 专题活动
     */
    Route::get('activity/special/apply-list', 'SpecialActivityController@applyLists');   // 专题活动报名列表
    Route::get('activity/special/apply-history', 'SpecialActivityController@actList');   // 店铺报名历史列表
    Route::post('activity/special/apply', 'SpecialActivityController@apply');   // 报名活动
    Route::get('activity/special/detail/{id?}', 'SpecialActivityController@detail'); //专题活动详情

    /*
     * 评价
     */
    Route::get('rate/lists', 'RateController@lists'); // 评价列表
    Route::get('rate/detail', 'RateController@detail'); // 评价详情
    Route::post('rate/reply', 'RateController@reply'); // 回复评价
    Route::post('rate/appeal/add', 'RateAppealController@appeal'); // 评价申诉
    Route::get('rate/appeal/lists', 'RateAppealController@lists'); // 申诉列表
    Route::get('rate/appeal/detail', 'RateAppealController@detail'); // 申诉详情

    /*
       * 店铺分类
       */
    Route::get('shopCats/lists', 'ShopCatsController@lists'); // 店铺分类列表
    Route::get('shopCats/allClassTree', 'ShopCatsController@allClassTree'); // 店铺分类树列表
    Route::post('shopCats/storeCat', 'ShopCatsController@storeCat'); // 增加店铺分类
    Route::post('shopCats/updateCat', 'ShopCatsController@updateCat'); // 更新店铺分类
    Route::get('shopCats/removeCat/{id?}', 'ShopCatsController@removeCat'); // 删除店铺分类

    /*
      * 店铺商品报警
      */
    Route::get('storePolice/goodsPolice', 'StorePoliceController@goodsPolice'); //库存报警商品
    Route::get('storePolice/storePolice', 'StorePoliceController@storePolice'); //查看报警值
    Route::post('storePolice/saveStorePolice', 'StorePoliceController@saveStorePolice'); //设置报警值
    Route::post('storePolice/updateStorePolice', 'StorePoliceController@updateStorePolice'); //更新报警值


    /*
     * 物流模板
     */
    Route::get('logistics/templatesLists', 'LogisticsTemplates@lists'); //物流模板列表
    Route::get('logistics/templatesDetailView', 'LogisticsTemplates@detailView'); //物流模板详情
    Route::post('logistics/addTemplates', 'LogisticsTemplates@addTemplates'); //添加物流模板
    Route::post('logistics/updateTemplates', 'LogisticsTemplates@updateTemplates'); //更新物流模板
    Route::get('logistics/removeTemplates', 'LogisticsTemplates@remove'); //删除模板

    /**
     * 物流运费模板
     */
    Route::get('ship/list', 'ShopShipController@list'); //物流运费模板列表
    Route::get('ship/detail/{id?}', 'ShopShipController@detail'); //物流运费模板详情
    Route::post('ship/save', 'ShopShipController@saveData'); //保存物流运费模板
    Route::get('ship/del/{id?}', 'ShopShipController@del'); //删除物流运费模板
    Route::get('ship/set-default/{id?}', 'ShopShipController@setDefault'); //设置默认模板
    Route::get('ship/open/{id?}', 'ShopShipController@open'); //启用模板
    Route::get('ship/close/{id?}', 'ShopShipController@close'); //关闭模板

    /*
     * 商家报表
     */
    Route::post('Stat/index', 'StatController@index'); //商家运营状况


    /*
     * 秒杀报名
     */
    Route::get('secKill/secKillAppliesLists', 'SecKillController@SecKillAppliesLists');   // 秒杀活动列表
    Route::get('secKill/secKillRegisterList', 'SecKillController@SecKillRegisterList');   //  秒杀活动报名列表
    Route::get('secKill/secKillGoodList', 'SecKillController@SecKillGoodList');   //  秒杀活动报名商品
    Route::get('secKill/registeredDetail', 'SecKillController@RegisteredDetail'); // 秒杀活动详情
    Route::get('secKill/registeredApply', 'SecKillController@RegisteredApply'); // 活动报名页面
    Route::post('secKill/registeredApplySave', 'SecKillController@RegisteredApplySave'); // 秒杀活动保存提交数据
    Route::post('secKill/registeredApplyEdit', 'SecKillController@RegisteredApplyEdit'); // 秒杀活动编辑
    Route::get('secKill/goodList', 'SecKillController@GoodList'); // 秒杀活动按规格返回商品
    Route::get('secKill/secKillGoodList', 'SecKillController@SecKillGoodList'); // 参加秒杀活动的商品



    /*
     * 团购
     */
    Route::get('group/groupList', 'GroupController@GroupList');   //  活动报名主商品
    Route::get('group/groupGoodList', 'GroupController@GroupGoodList');   //  活动报名明细商品（子商品）
    Route::get('group/groupApplyDelete', 'GroupController@GroupApplyDelete');   //  团购活动删除
    Route::get('group/groupApplyDeleteForce', 'GroupController@GroupApplyDeleteForce');   //  团购活动强制删除
    Route::get('group/registeredDetail', 'GroupController@RegisteredDetail'); // 团购活动详情
    Route::post('group/registeredApplySave', 'GroupController@RegisteredApplySave'); // 团购保存提交数据



    /*
     * 运营报表
     */
    Route::post('stats/tradeAnalysis', 'StatController@tradeAnalysis'); //交易数据统计
    Route::post('stats/goodsAnalysis', 'StatController@goodsListAnalysis'); //商品数据统计

    /*
    * 角色管理
    */
    Route::get('role/lists', 'RoleController@lists');    // 角色列表
    Route::post('role/create', 'RoleController@create');    // 新增角色
    Route::post('role/update', 'RoleController@update');    // 更新角色
    Route::get('role/delete', 'RoleController@delete');    // 删除角色
    Route::get('role/detail', 'RoleController@detail');    // 角色详情

    /*
     * 后台管理员
     */
    Route::get('admin/lists', 'ShopSellersController@lists');    // 管理员列表
    Route::post('admin/create', 'ShopSellersController@create');    // 新增管理员
    Route::post('admin/update', 'ShopSellersController@update');    // 更新管理员
    Route::get('admin/delete', 'ShopSellersController@delete');    // 删除管理员
    Route::get('admin/detail', 'ShopSellersController@detail');    // 管理员详情

     //结算报表
    Route::get('tradeSettlement/tradeDayDetailLists', 'TradeSettlementController@TradeDayDetailLists'); // 日结算明细列表
    Route::get('tradeSettlement/tradeDayLists', 'TradeSettlementController@TradeDayLists'); // 日结列表
    Route::get('tradeSettlement/tradeDayDetailGoodsLists', 'TradeSettlementController@TradeDayDetailGoodsLists'); // 日结算商品明细
    Route::get('tradeSettlement/tradeMonthLists', 'TradeSettlementController@TradeMonthLists'); // 月结列表
    Route::get('tradeSettlement/tradeDayListsDown', 'TradeSettlementController@TradeDayListsDown'); // 日结下载
    Route::get('tradeSettlement/tradeMonthListsDown', 'TradeSettlementController@TradeMonthListsDown'); // 月结下载

    /**
     * 店铺挂件配置
     */
    Route::get('siteConfig/items', 'ShopSiteConfigController@Items');    // 获取店铺挂件配置
    Route::post('siteConfig/tmplStore', 'ShopSiteConfigController@TmplStore');    // 添加店铺挂件
    Route::post('siteConfig/siteStore', 'ShopSiteConfigController@SiteStore');    // 添加店铺配置

    /**
     * 店铺分销推物功能控制start
     */
    Route::get('shopAttr/detail', 'ShopAttrController@detail');    // 查看推物控制
    Route::post('shopAttr/edit', 'ShopAttrController@edit');      //配置推物
    Route::get('shopAttr/promoGoodCheck', 'ShopAttrController@PromoGoodCheck');//商品是否可以设置推物返利
    //-------店铺分销推物功能控制end------

    //-----------------退款列表模块start--------------
    Route::get('trade/refunds/down', 'TradeAfterRefundController@TradeRefundsDown');  // 退款列表导出
    Route::get('trade/refundsLists', 'TradeAfterRefundController@refundsLists'); // 退款列表

    //-----------------退款列表模块end----------------

    //-----------------退货模块start--------------
    Route::get('trade/refund-goods-lists', 'TradeAfterSalesController@refundGoodsLists'); // 列表
    Route::post('trade/refund-goods-down', 'TradeAfterSalesController@refundGoodsDown'); // 列表导出
    //-----------------退货模块end----------------


    //-----------------确认收货报表模块start--------------
    Route::get('trade/confirm-order-lists', 'TradeController@confirmOrderLists'); // 列表
    Route::post('trade/confirm-order-down', 'TradeController@confirmOrdersDown'); // 列表导出
    //-----------------确认收货报表模块end----------------


    //-----------------成本报表模块模块start--------------
    Route::get('trade/goods-cost-lists', 'TradeController@GoodsCostLists'); // 列表
    Route::post('trade/goods-cost-down', 'TradeController@GoodsCostDown'); // 列表导出
    //-----------------成本报表模块模块end----------------

    //-----------------自提核销列表start--------------
    // Route::get('goods/self-extracting-lists', 'GoodsController@selfExtractingLists'); // 列表
    Route::get('trade/self-extracting-lists', 'TradeController@selfExtractingLists'); // 列表
    Route::post('trade/self-extracting-down', 'TradeController@selfExtractingDown'); // 导出
    //-----------------自提核销列表end----------------

    //-----------------商品销售报表模块start--------------
    Route::get('trade/good-sale-list', 'TradeController@GoodSaleList');  // 商品统计列表
    Route::post('trade/good-sale-down', 'TradeController@GoodSaleDown');  // 商品统计列表导出
    //-----------------商品销售报表模块end----------------

    //-----------------商品模块start--------------
    Route::post('goods/down', 'GoodsController@goodDown');   // 导出
    //-----------------商品模块end----------------


    //-----------------新导出模块start--------------
    Route::post('trade/new-export-filter', 'TradeController@newTradeOrder'); // 筛选导出订单
    Route::post('trade/new-goods-cost-down', 'TradeController@newGoodsCostDown'); // 列表导出

    /**
     * 导出列表
     */
    Route::get('downloadService/downLoadList', 'DownloadServiceController@DownLoadList'); // 导出下载列表
    Route::get('downloadService/delete', 'DownloadServiceController@Delete'); // 删除导出记录
    //-----------------新导出模块end----------------

    Route::get('user/getRule', 'ArticleController@getRule'); // 获取规则

    /**
     * 店铺回寄地址模块start
     */
    Route::get('shop-address/list','ShopRelAddrController@lists')->name('ShopRelAddr.list'); // 地址列表
    Route::post('shop-address/store','ShopRelAddrController@store')->name('ShopRelAddr.store'); // 地址添加
    Route::get('shop-address/delete','ShopRelAddrController@delete')->name('ShopRelAddr.delete'); // 地址删除
    Route::post('shop-address/update','ShopRelAddrController@update')->name('ShopRelAddr.update'); // 地址修改
    Route::get('shop-address/detail','ShopRelAddrController@detail')->name('ShopRelAddr.detail'); // 地址详情
    Route::post('shop-address/set','ShopRelAddrController@set')->name('ShopRelAddr.set'); // 设置默认地址

});
