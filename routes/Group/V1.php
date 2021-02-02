<?php

/**
 * 集团模块路由  Group
 * @Author: nlx
 * @Date:   2020-03-02 17:47:54
 */

use Illuminate\Support\Facades\Route;

Route::namespace('ShopEM\Http\Controllers\Group\V1')->group(function () {
    Route::post('passport/login', 'PassportController@login')->name('group.passport.login');  // 平台后台登录

    Route::post('gm-platform/updateBaseConfig/{gm_id?}', 'GroupManagePlatform@updateReConfig');
    Route::post('key-manage/sendCode', 'KeyManageController@sendCode');

    

});

Route::namespace('ShopEM\Http\Controllers\Group\V1')->middleware('auth:group_users')->group(function () {
   // Route::namespace('ShopEM\Http\Controllers\Group\V1')->group(function () {

        Route::get('passport/logout', 'PassportController@logout')->name('group.passport.logout');    // 平台后台退出

    Route::post('/upload/image', 'UploadController@image')->name('upload.image'); // 上传图片
    Route::post('/upload/apk', 'UploadController@apk')->name('upload.apk'); // 上传apk
    Route::get('/apk/get', 'UploadController@apkGet')->name('get.apk'); // 获取apk
    Route::get('/apk/list', 'UploadController@apkList')->name('apk.list'); // apk列表

    Route::post('/versions/add', 'UploadController@versionsAdd')->name('versions.add'); // 上传apk
	/**
	 * 平台项目关系
	 */
	Route::get('adminuser/lists', 'PlatformAdminUser@lists')->name('group.adminuser.lists');
	Route::post('adminuser/add', 'PlatformAdminUser@addAdmin')->name('group.adminuser.add');
	Route::post('adminuser/resetPwd', 'PlatformAdminUser@resetPwd')->name('group.adminuser.resetpwd');
    Route::post('adminuser/switch', 'PlatformAdminUser@account_switch')->name('group.adminuser.switch'); // 账户开关

	Route::get('platform/lists', 'GroupManagePlatform@lists')->name('group.platform.lists');
	Route::post('platform/add', 'GroupManagePlatform@addPlatform')->name('group.platform.add');
	Route::post('platform/update', 'GroupManagePlatform@updatePlatform')->name('group.platform.update');
    Route::post('platform/act', 'GroupManagePlatform@actPlatform')->name('group.platform.act');
    Route::get('platform/detail/{id?}', 'GroupManagePlatform@detail')->name('group.platform.detail');
    Route::post('platform/update-point', 'GroupManagePlatform@updatePoint')->name('group.platform.edit-point');    // 更新积分设置
    Route::post('platform/listorder', 'GroupManagePlatform@updateListOrder')->name('group.platform.listorder');    // 更新权重
    Route::post('platform/updateInfo', 'GroupManagePlatform@updateInfo')->name('group.platform.updateInfo');    // 更新
	/*
	*商品
	*/
	Route::get('goods/lists', 'GoodsController@lists')->name('group.goods.lists');   // 商品列表

    /*
     * Album
     */
    Route::get('album/pics', 'AlbumController@pics')->name('group.album.pics');  // 图片相册
    Route::post('album/del-id', 'AlbumController@delById')->name('group.album.del-id');  // 根据ID删除图片
    Route::post('album/del-url', 'AlbumController@delByUrl')->name('group.album.del-url');  // 根据url删除图片

    Route::get('live/user/list', 'LiveController@userList')->name('live.user.live');    //主播列表
    Route::get('live/platforms/shops', 'LiveController@platformsShops')->name('live.platforms.shops');    //品牌下的所有门店
    Route::post('live/platform/binding', 'LiveController@bindingPlatform')->name('live.platform.binding');    //主播绑定品牌店

    Route::post('live/notice/add', 'LiveController@noticeAdd')->name('live.noticeAdd');    //
    Route::post('live/notice/save', 'LiveController@noticeSave')->name('live.noticeSave');    //
    Route::get('live/notice/list', 'LiveController@noticeList')->name('live.noticeList');    //
    Route::get('live/notice/get', 'LiveController@noticeGet')->name('live.noticeGet');    //
    Route::post('live/notice/delete', 'LiveController@noticeDelete')->name('live.noticeDelete');    //



	/*
    * 商品分类
    */
    Route::get('goodsClass/lists', 'GoodsClassController@lists')->name('group.goodsClass.lists');    // 分类列表
    Route::get('goodsClass/detail/{id?}', 'GoodsClassController@detail')->name('group.goodsClass.detail');  // 分类详情
    Route::post('goodsClass/add', 'GoodsClassController@store')->name('group.goodsClass.add');    // 添加商品分类
    Route::post('goodsClass/edit', 'GoodsClassController@goodsClassUpdate')->name('group.goodsClass.edit');    // 更新分类数据
    Route::get('goodsClass/delete/{id?}', 'GoodsClassController@delete')->name('group.goodsClass.delete'); // 删除分类
    Route::get('goods/allClassTree', 'GoodsClassController@allClassTree')->name('group.goodsClass.allClassTree');   // 商品分类树

    /*
    * 品牌
    */
    Route::get('brand/lists', 'BrandController@lists')->name('group.brand.lists');   // 品牌列表
    Route::get('brand/detail/{id?}', 'BrandController@detail')->name('group.brand.detail');  // 品牌详情
    Route::post('brand/add', 'BrandController@store')->name('group.brand.add');    // 添加品牌
    Route::post('brand/edit', 'BrandController@update')->name('group.brand.edit');    // 更新品牌
    Route::get('brand/delete/{id?}', 'BrandController@delete')->name('group.brand.delete'); // 删除品牌

     /*
    * 规格
    */
    Route::get('goodsSpec/lists', 'SpecController@lists')->name('group.goodsSpec.lists');   // 规格列表
    Route::get('goodsSpec/detail/{id?}', 'SpecController@detail')->name('group.goodsSpec.detail');  // 规格详情
    Route::post('goodsSpec/add', 'SpecController@store')->name('group.goodsSpec.add');    // 添加规格
    Route::post('goodsSpec/edit', 'SpecController@update')->name('group.goodsSpec.edit');    // 更新规格
    Route::get('goodsSpec/delete/{id?}', 'SpecController@delete')->name('group.goodsSpec.delete'); // 删除规格

     /**
     * 店铺楼层
     */
    Route::get('shopFloors/lists', 'ShopFloorsController@lists')->name('group.shopFloors.lists');  //店铺楼层列表

     /*
    * 店铺管理
    */
    Route::get('shop/lists', 'ShopController@lists')->name('group.shop.lists');  // 店铺列表

     /*
     * 会员
     */
    Route::get('member/lists', 'MemberController@lists')->name('group.member.lists');   // 会员列表
    Route::get('member/search', 'MemberController@search')->name('group.member.search');   // 筛选会员列表
    Route::post('member/export', 'MemberController@filterExport')->name('group.member.export');   // 筛选导出会员

    /**
     * 会员积分管理
     */
    Route::get('member/pointLog', 'MemberController@pointLogList')->name('group.member.pointLogList'); // 会员积分明细表

    /*
     * 订单
     */
    Route::get('trade/lists', 'TradeController@lists')->name('group.trade.lists'); // 订单列表
    Route::get('trade/cancelLists', 'TradeController@cancelLists')->name('group.trade.cancelLists');//取消订单列表
    Route::get('trade/detail', 'TradeController@detail')->name('group.trade.detail'); // 订单详情
    Route::post('trade/export/filter', 'TradeController@filterExport')->name('group.trade.export.filter'); // 筛选导出订单
    Route::get('trade/stock/return/list', 'TradeController@getTradeStockReturnLogList')->name('group.trade.stock-return-list'); // 订单库存回传日志列表

     /*
     * 售后
     */
    Route::get('trade/afterSalesLists', 'TradeAfterSalesController@Lists')->name('group.trade.afterSalesLists'); // 售后列表
    Route::get('trade/afterSalesDetail/{id?}', 'TradeAfterSalesController@detailBasic')->name('group.trade.afterSalesDetail'); // 售后明细
    Route::get('trade/refundsLists', 'TradeAfterRefundController@refundsLists')->name('group.trade.refundsLists'); // 退款列表
    Route::get('trade/afterSales/down', 'TradeAfterSalesController@TradeAfterSalesDown')->name('group.trade.aftersales.down'); // 售后列表导出
    Route::get('trade/refunds/down', 'TradeAfterRefundController@TradeRefundsDown')->name('group.trade.refunds.down'); // 退款列表导出
     /*
     * 评价
     */
    Route::get('rate/lists', 'RateController@lists')->name('group.rate.lists'); // 评价列表
    Route::get('rate/detail', 'RateController@detail')->name('group.rate.detail'); // 评价详情

    /*
     * 首页
     */
    Route::get('index/detail', 'IndexController@detail')->name('group.index.detail');   //首页数据
    Route::get('index/currentTime', 'IndexController@currentTime')->name('group.index.currentTime');   //今天实时概况
    Route::get('index/selectDetail', 'IndexController@selectDetail')->name('group.index.selectDetail');   //时间筛选首页数据

     /**
     * 报表类数据
     */
    Route::post('stats/analysis', 'StatController@analysis')->name('group.stats.analysis'); //经营概况
    Route::post('stats/tradeAnalysis', 'StatController@tradeAnalysis')->name('group.stats.tradeAnalysis'); //交易数据统计
    Route::post('stats/storeAnalysis', 'StatController@storeListAnalysis')->name('group.stats.storeAnalysis'); //店铺数据统计
    Route::post('stats/goodsAnalysis', 'StatController@goodsListAnalysis')->name('group.stats.goodsAnalysis'); //商品数据统计
    Route::post('stats/userAnalysis', 'StatController@userAnalysis')->name('group.stats.userAnalysis'); //会员排行

    //结算报表
    Route::get('tradeSettlement/tradeDayLists', 'TradeSettlementController@TradeDayLists')->name('group.tradeSettlement.tradeDayLists'); // 日结列表
    Route::get('tradeSettlement/tradeMonthLists', 'TradeSettlementController@TradeMonthLists')->name('group.tradeSettlement.tradeMonthLists'); // 月结列表
    Route::get('tradeSettlement/tradeDayListsDown', 'TradeSettlementController@TradeDayListsDown')->name('group.tradeSettlement.tradeDayListsDown'); // 日结下载
    Route::get('tradeSettlement/tradeMonthListsDown', 'TradeSettlementController@TradeMonthListsDown')->name('group.tradeSettlement.tradeMonthListsDown'); // 月结下载
     Route::get('tradeSettlement/tradeDayDetailLists', 'TradeSettlementController@TradeDayDetailLists')->name('group.tradeSettlement.tradeDayDetailLists'); // 日结算明细列表
    Route::get('tradeSettlement/tradeDayDetailGoodsLists', 'TradeSettlementController@TradeDayDetailGoodsLists')->name('group.tradeSettlement.tradeDayDetailGoodsLists'); // 日结算商品明细

    /**
     * 平台系统日志
     */
    Route::get('adminLogs/lists', 'AdminLogsController@lists')->name('group.adminLogs.lists');    // 系统日志列表

    /*
     * 角色管理
     */
    Route::get('role/lists', 'RoleController@lists')->name('group.role.lists');    // 角色列表
    Route::post('role/create', 'RoleController@create')->name('group.role.create'); // 新增角色
    Route::get('role/detail', 'RoleController@detail')->name('group.role.detail');    // 角色详情
    Route::get('role/delete', 'RoleController@delete')->name('group.role.delete');    // 删除角色
    Route::post('role/update', 'RoleController@update')->name('group.role.update');    // 更新角色

    /*
     * 角色权限菜单管理
     */
    Route::get('permission/manage', 'PermissionMenuController@lists')->name('group.permission.manage');    // 权限管理
    Route::get('permission/platformRoutes', 'PermissionMenuController@platformRoutes')->name('group.permission.platform.routes');    // 平台后台路由
    Route::post('permission/createMenu', 'PermissionMenuController@create')->name('group.permission.create.menu');
    // 新增后台管理菜单
    Route::get('permission/menuDetail', 'PermissionMenuController@detail')->name('group.permission.menu.detail');    // 后台管理菜单详情
    Route::get('permission/menuLists', 'PermissionMenuController@lists')->name('group.permission.menu.lists');    // 后台管理菜单列表
    Route::post('permission/updateMenu', 'PermissionMenuController@update')->name('group.permission.update.menu');    // 更新后台管理菜单
    Route::get('permission/deleteMenu', 'PermissionMenuController@delete')->name('group.permission.delete.menu');    // 删除后台管理菜单

     /*
     * 后台管理员
     */
    Route::get('admin/lists', 'AdminController@lists')->name('group.admin.lists');    // 管理员列表
    Route::get('admin/detail', 'AdminController@detail')->name('group.admin.detail');    // 管理员详情
    Route::post('admin/create', 'AdminController@create')->name('group.admin.create');    // 新增管理员
    Route::post('admin/update', 'AdminController@update')->name('group.admin.update');    // 更新管理员
    Route::get('admin/delete', 'AdminController@delete')->name('group.admin.delete');    // 删除管理员

    /*
     * 物流
     */
    Route::get('logistics/logisticsLists', 'LogisticsController@logisticsLists')->name('group.logistics.logisticsLists'); // 物流列表
    Route::post('logistics/LogisticsAdd', 'LogisticsController@LogisticsAdd')->name('group.logistics.LogisticsAdd'); // 物流公司添加
    Route::post('logistics/LogisticsEdit', 'LogisticsController@LogisticsEdit')->name('group.logistics.LogisticsEdit'); // 物流公司编辑
    Route::get('logistics/LogisticsDel/{id?}', 'LogisticsController@LogisticsDel')->name('group.logistics.LogisticsDel'); // 物流公司删除

     /*
     * 积分商品分类
     */
     Route::get('pointGoodClass/lists', 'PointGoodsClassController@lists')->name('group.pointGoodClass.lists');//积分商品分类列表
     Route::post('pointGoodClass/create', 'PointGoodsClassController@create')->name('group.pointGoodClass.create');//新增积分商品分类
     Route::get('pointGoodClass/update', 'PointGoodsClassController@update')->name('group.pointGoodClass.update');//更新积分商品分类
     Route::get('pointGoodClass/delete', 'PointGoodsClassController@delete')->name('group.pointGoodClass.delete');//删除分类

    /*
    * 支付类型代码
    */
    Route::get('paymentType/lists', 'PaymentTypeController@lists')->name('paymentType.lists');   // 支付类型代码列表
    Route::get('paymentType/type', 'PaymentTypeController@payType')->name('paymentType.type');   // 支付类型下拉列表
    Route::get('paymentType/detail/{id?}', 'PaymentTypeController@detail')->name('paymentType.detail');//支付类型代码详情
    Route::get('paymentType/delete/{id?}', 'PaymentTypeController@delete')->name('paymentType.delete');//删除支付类型代码
    Route::post('paymentType/add', 'PaymentTypeController@createPayType')->name('paymentType.add');//添加支付类型代码
    Route::post('paymentType/edit', 'PaymentTypeController@updatePayType')->name('paymentType.edit');//更新支付类型代码
     /*
     * 文章
     */
     Route::get('article/lists', 'ArticleController@lists')->name('group.article.lists');    // 文章列表
     Route::get('article/verify', 'ArticleController@verify')->name('group.article.lists');    // 审核文章
     Route::get('article/detail/{id?}', 'ArticleController@detail')->name('group.article.detail');  // 文章详情

      /*
     * 会员活动
     */
     Route::get('member-activity/lists', 'MemberActivityController@lists')->name('member-activity.lists');//活动列表
     Route::get('member-activity/verify', 'MemberActivityController@verify')->name('member-activity.verify');//活动审核

    //-----------------UV报表模块start--------------
    Route::get('uv-trade/lists','UVStatisticsController@lists')->name('group.UVTrade.lists'); ;  // 列表
    Route::get('uv-trade/export', 'UVStatisticsController@filterExport')->name('group.UVTrade.export');   // 筛选导出会员    //-----------------UV报表模块end----------------

    //-----------------获取品牌名称列表start--------------
    Route::get('brand/get-brand-lists', 'BrandController@getBrandlists')->name('group.brand.get');
//-----------------获取品牌名称列表end----------------


    //-----------------支付订单模块start--------------
    Route::get('trade/payment/lists', 'TradePaymentController@lists')->name('group.trade.payment.lists'); // 订单支付单关联列表
    Route::post('trade/payment/down', 'TradePaymentController@PaymentDown')->name('group.trade.payment.down'); // 订单支付单关联列表导出

    //-----------------支付订单模块end----------------


    //-----------------退货模块start--------------
    Route::get('trade/refund-goods-lists','TradeAfterSalesController@refundGoodsLists')->name('group.trade.refund.goods'); // 列表
    Route::post('trade/refund-goods-down', 'TradeAfterSalesController@refundGoodsDown')->name('group.trade.refund.goods.down'); // 列表导出
    //-----------------退货模块end----------------


    //-----------------确认收货报表模块start--------------
    Route::get('trade/confirm-order-lists', 'TradeController@confirmOrderLists')->name('group.trade.refund.goods'); // 列表
    Route::post('trade/confirm-order-down', 'TradeController@confirmOrdersDown')->name('group.trade.refund.goods.down'); // 列表导出
    //-----------------确认收货报表模块end----------------


    //-----------------成本报表模块模块start--------------
    Route::get('trade/goods-cost-lists', 'TradeController@GoodsCostLists')->name('group.trade.goods.cost'); // 列表
    Route::post('trade/goods-cost-down', 'TradeController@newGoodsCostDown')->name('group.trade.goods.cost.down'); // 列表导出

    Route::post('trade/new-goods-cost-down', 'TradeController@newGoodsCostDown')->name('group.trade.new.goods.cost.down'); // 新的成本报表导出

    //-----------------成本报表模块模块end----------------


    /*----------------------- 后台审核推广员模块start --------------------*/
    Route::get('config/items', 'ConfigController@items')->name('group.items.items'); // 分销基础配置信息
    Route::post('config/add', 'ConfigController@store')->name('group.items.add'); // 分销基础配置添加

    Route::get('applyPromoter/lists', 'PromoterController@lists')
        ->name('group.applyPromoter.lists'); // 申请的列表
    Route::get('applyPromoter/detail/{id?}', 'PromoterController@detail')
        ->name('group.applyPromoter.detail');// 申请的详情
    Route::post('applyPromoter/examine', 'PromoterController@examine')
        ->name('group.applyPromoter.examine'); // 审核申请

    Route::get('applyPromoter/export/lists', 'PromoterController@exportListsData')
        ->name('group.applyPromoter.exportListsData'); // 导出列表数据

    Route::get('applyPromoter/goodsSpreadLists', 'PromoterController@GoodsSpreadLists')
        ->name('group.applyPromoter.goodsSpreadLists'); // 审核申请

    Route::post('applyPromoter/setDepartment', 'PromoterController@SetDepartment')
        ->name('group.applyPromoter.setDepartment'); // 设置部门
    Route::get('applyPromoter/relatedLogsList', 'PromoterController@RelatedLogsList')
        ->name('group.applyPromoter.relatedLogsList'); // 获取所有推物信息列表

    Route::get('shopAttr/lists', 'ShopAttrController@lists')
        ->name('group.shopAttr.lists'); //查看店铺配置
    Route::post('shopAttr/deploy', 'ShopAttrController@deploy')
        ->name('group.shopAttr.deploy'); //配置店铺
    Route::post('shopAttr/controlPromo', 'ShopAttrController@controlPromo')
        ->name('group.shopAttr.controlPromo'); //一键控制店铺

    Route::get('userDeposits/userDepositCashesList', 'UserDepositsController@UserDepositCashesList')
        ->name('group.userDeposits.userDepositCashesList'); // 会员申请提现列表
    Route::get('userDeposits/getUserApplyDetail', 'UserDepositsController@GetUserApplyDetail')
        ->name('group.userDeposits.getUserApplyDetail'); // 会员提现详情
    Route::post('userDeposits/examine', 'UserDepositsController@Examine')
        ->name('group.userDeposits.examine'); // 审核
    Route::get('userDeposits/tradeEstimatesLists', 'UserDepositsController@TradeEstimatesLists')
        ->name('group.userDeposits.tradeEstimatesLists'); // 会员推广订单列表(预估收益)
    Route::get('userDeposits/tradeRewardsLists', 'UserDepositsController@TradeRewardsLists')
        ->name('group.userDeposits.TradeRewardsLists'); // 会员推广订单列表(实际收益收益)
    Route::get('userDeposits/userDepositLogsLists', 'UserDepositsController@UserDepositLogsLists')
        ->name('group.userDeposits.userDepositLogsLists'); // 会员收入日志
    Route::get('userDeposits/userDepositLists', 'UserDepositsController@UserDepositLists')
        ->name('group.userDeposits.userDepositLists'); // 会员账户列表
    Route::get('userDeposits/userDepositDetail', 'UserDepositsController@UserDepositDetail')
        ->name('group.userDeposits.userDepositDetail'); // 会员账户明细

    Route::post('member/becomePromoter', 'MemberController@becomePromoter')
        ->name('member.becomePromoter');  // 后台授权会员成为推广员


    Route::post('partners/setPartners', 'PromoterController@SetPartners')
        ->name('group.partners.setPartners'); // 直接授权分销(身份1-推广员,2-小店,3-分销商,4-经销商)
    Route::get('partners/setPartnersLog', 'PromoterController@SetPartnersLog')
        ->name('group.partners.setPartnersLog'); // 会员账户明细
    Route::post('partners/setPartnersRelated', 'PromoterController@SetPartnersRelated')
        ->name('group.partners.setPartnersRelated'); // 改合伙人关联
    Route::post('partners/changePartnersRelated', 'PromoterController@ChangePartnersRelated')
        ->name('group.partners.changePartnersRelated'); // 修改某个会员的上下级关系
    Route::get('partners/getUserInfo', 'PromoterController@GetUserInfo')
        ->name('group.partners.getUserInfo'); // 会员信息(包含上下级)
//    Route::get('partners/unbindRelated', 'PromoterController@UnbindRelated')
//        ->name('group.partners.unbindRelated'); // 推广员解绑小店
    Route::post('partners/unfreezePartners', 'PromoterController@UnfreezePartners')
        ->name('partners.unfreezePartners'); // 冻结或者解冻会员


    Route::get('ranking/userRewardRankingList', 'UserDepositsController@UserRewardRankingList')
        ->name('group.Ranking.userRewardRankingList'); // 分销个人销售排行榜
    Route::get('ranking/userRewardRankingListDown', 'UserDepositsController@UserRewardRankingListDown')
        ->name('group.Ranking.userRewardRankingListDown'); // 分销个人销售排行榜

    Route::get('user/promoter/lists','MemberController@searchExtension')
        ->name('group.user.promoter.lists'); # 推广员列表
    Route::get('transmit/userRecommendDetai','ActivitiesTransmitController@UserRecommendDetai')->name('transmit.userRecommendDetai'); // 会员下级详情


    Route::get('ranking/promoterLists', 'UserDepositsController@PromoterLists')
        ->name('group.Ranking.promoterLists'); // 会员佣金统计汇总表
    Route::get('ranking/promoterListsDown', 'UserDepositsController@PromoterListsDown')
        ->name('group.Ranking.promoterListsDown'); // 会员佣金统计汇总表下载
    Route::get('ranking/groupCollectLists', 'UserDepositsController@GroupCollectLists')
        ->name('group.Ranking.groupCollectLists'); // 分销团队-销售排行榜-汇总表

    Route::get('ranking/partnerLists', 'UserDepositsController@PartnerLists')
        ->name('group.Ranking.partnerLists'); // 分销团队-销售排行榜-汇总表
    Route::get('ranking/promoterShopLists', 'UserDepositsController@PromoterShopLists')
        ->name('group.Ranking.promoterShopLists'); // 小店佣金统计汇总表
    Route::get('ranking/promoterShopListsDown', 'UserDepositsController@PromoterShopListsDown')
        ->name('group.Ranking.promoterShopListsDown'); // 小店佣金统计汇总下载
    /*--------------- 后台审核推广员模块end ----------------*/

    //-----------------商品销售报表模块start--------------
    Route::get('trade/good-sale-list', 'TradeController@GoodSaleList')->name('group.good.sale.list');  // 商品统计列表
    Route::post('trade/good-sale-down', 'TradeController@GoodSaleDown')->name('group.good.sale.down');  // 商品统计列表导出
    //-----------------商品销售报表模块end----------------

    //-----------------商品模块start--------------
    Route::post('goods/down', 'GoodsController@goodDown')->name('group.goods.down');   // 导出
    //-----------------商品模块end----------------

    //-----------------线上对账模块start--------------
    Route::post('wechat/trade-import', 'WechatTradeCheckController@tradeImport')->name('group.trade.import');   //交易导入
    Route::get('wechat/trade-import-list', 'WechatTradeCheckController@getTradeImportList')->name('group.trade.get_import_list');   //获取导入的微信交易数据
    Route::post('wechat/refund-import', 'WechatTradeCheckController@refundImport')->name('group.refund.import');   //退款导入
    Route::post('wechat/project-import', 'WechatTradeCheckController@projectImport')->name('group.project.import');   //项目导入
    Route::get('wechat/project-import-list', 'WechatTradeCheckController@getProjectImportList')->name('group.project.get_import_list');   //获取导入的项目数据
    Route::get('wechat/check-list', 'WechatTradeCheckController@list')->name('group.check.list');   //查看
    Route::get('wechat/check-detail', 'WechatTradeCheckController@detail')->name('group.check.detail');   //详情
    Route::post('wechat/check-update-processing', 'WechatTradeCheckController@updateProcessing')->name('group.update.processing');   //修改
    Route::post('wechat/check-delete', 'WechatTradeCheckController@delete')->name('group.check.delete'); //删除
    Route::get('wechat/check-abnormal-list', 'WechatTradeCheckController@abnormalList')->name('group.abnormal.list'); //异常列表
    Route::post('wechat/check-export', 'WechatTradeCheckController@exportList')->name('group.check.export'); //导出列表
    Route::post('wechat/check-abnormal-export', 'WechatTradeCheckController@exportAbnormalList')->name('group.abnormal.export'); //导出异常列表
    //-----------------线上对账模块end----------------
    //-----------------店铺列表start--------------
    Route::post('shop/export-list', 'ShopController@ExportList')->name('group.shop.export');  // 店铺列表导出
    //-----------------店铺列表end----------------


    //-----------------日销售报表模块start--------------
    Route::get('trade/daily-sales-list', 'TradeController@DailySalesList')->name('group.daily.sales.list');  // 销售日报列表
    Route::post('trade/daily-sales-down', 'TradeController@DailySalesDown')->name('group.daily.sales.down');  // 销售日报列表导出
    //-----------------日销售报表模块end----------------

    //-----------------新导出模块start--------------
    Route::post('member/userAccountDown', 'MemberController@userAccountDown')->name('group.new.member.userAccountDown');  //会员信息导出
    Route::post('trade/new-export-filter', 'TradeController@newTradeOrder')->name('group.new.trade.export.filter'); // 筛选导出订单
    Route::post('trade/new-payment-down', 'TradePaymentController@newPaymentDown')->name('group.new.trade.payment.down'); // 订单支付单关联列表导出
    /**
     * 导出列表
     */
    Route::get('downloadService/downLoadList', 'DownloadServiceController@DownLoadList')->name('group.downloadService.downLoadList'); // 导出下载列表
    Route::get('downloadService/delete', 'DownloadServiceController@Delete')->name('group.downloadService.delete'); // 删除导出记录
    //-----------------新导出模块end----------------



    /**
      * 规则
      */
     Route::get('rule/lists', 'RuleController@lists')->name('group.rule.lists');//规则列表
     Route::post('rule/save', 'RuleController@save')->name('group.rule.save');//新增规则
     Route::post('rule/update', 'RuleController@update')->name('group.rule.update');//更新规则
     Route::get('rule/detail/{id?}', 'RuleController@detail')->name('group.rule.detail');//规则详情
     Route::get('rule/delete', 'RuleController@delete')->name('group.rule.delete');//删除规则
     Route::get('rule/ruleType', 'RuleController@ruleType')->name('group.rule.ruleType');//规则类型

    /**
     * open-api
     */
    Route::post('openapi/entry', 'OpenApiController@entry');   //注册appid
    Route::get('open-api/fetch-Auth', 'OpenApiController@fetchApiAuthList');  //获取权限列表

     /**
      * 密钥管理
      */
    Route::get('key-manage/detail', 'KeyManageController@detail')->name('group.key.detail');//信息
    Route::get('key-manage/bind', 'KeyManageController@bindMobile')->name('group.key.bind');//绑定手机号
    Route::get('key-manage/unbind', 'KeyManageController@unBindMobile')->name('group.key.unbind');//解绑手机号
    Route::get('key-manage/show', 'KeyManageController@showKey')->name('group.key.show');//查看密钥
    Route::post('key-manage/save', 'KeyManageController@saveKey')->name('group.key.save');//保存密钥

    /**
     * 钱包支付配置
     */
    Route::get('wallet-config/info', 'PayWalletController@info')->name('group.wallet.config.info'); //钱包支付配置信息
    Route::post('wallet-config/save', 'PayWalletController@save')->name('group.wallet.config.save'); //钱包支付配置保存
    Route::any('wallet-config/physicalImg', 'PayWalletController@physicalCardImg')->name('group.wallet.physical.img');;
});

