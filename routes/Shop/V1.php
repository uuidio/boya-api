<?php
/**
 * @Filename Shop/V1.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

Route::namespace('ShopEM\Http\Controllers\Shop\V1')->group(function () {
    // 钱包日志解析
    Route::post('wallet/fetch-log', 'WalletController@fetchPushLog');

    // cms接口
    Route::post('/tickets/activity/getCode', 'TicketsActivityController@activeCodeDetail');
    Route::post('/tickets/activity/hand', 'TicketsActivityController@handApply');
    Route::post('/tickets/activity/checkpass', 'TicketsActivityController@checkpass');
    Route::get('/tickets/activity/table', 'TicketsActivityController@applyTable');
    Route::post('/tickets/activity/export', 'TicketsActivityController@filterExport');

    //微信code
    Route::get('code', 'WechatController@get_code');
    Route::get('openid', 'WechatController@get_access_token')->name('getOpenid');
    Route::get('wechatlogin', 'PassportController@wechatLogin');

    Route::post('wechat/jsapiconfig', 'WechatController@getApiSdk'); //微信jsapi配置
    Route::get('cache/clean-one/{id?}', 'IndexController@cleanOneCache');//清理单个会员的服务器cache

    // test
    Route::get('index/test', 'TestController@test1');
    Route::get('index/testDown', 'TestController@testDown');
    Route::get('test/xinpoll/create-account', 'TestController@createAccountTest');
    Route::get('test/bc/pay', 'TestController@getPayConfig');// 通商云获取支付配置测试
    Route::any('lanlnk/sql/update', 'TestController@updateSql');

    Route::get('index-item/nearby', 'IndexController@itemNearby'); //距离最近的集团项目
    Route::get('index-item/lists', 'IndexController@itemList'); //集团项目列表
    Route::post('index/turn64', 'IndexController@turn64');
    Route::get('index/makeArea', 'IndexController@makeChinaArea');

    Route::get('index/detail', 'IndexController@detail'); // 首页详情
    Route::get('index/imagePop', 'IndexController@imagePop'); // 首页图片弹窗

    Route::get('index/getIndexHot', 'IndexController@getIndexHot'); // 首页热卖商品

    Route::get('index/getRecommend', 'IndexController@getRecommend'); // 为你推荐
    Route::get('index/recommend-title', 'IndexController@getRecommendTitle'); // 为你推荐标题

    //留言
    Route::post('message', 'UserController@message'); // 留言

    /*
     * 商品
     */
    Route::get('goodsClass/lists', 'GoodsClassController@lists'); // 商品分类
    Route::get('goodsClass/siblingsLists', 'GoodsClassController@siblingsLists'); // 同辈分类

    Route::get('goods/lists', 'GoodsController@lists'); // 商品列表
    Route::get('goods/filter', 'GoodsController@filter'); // 商品筛选条件
    Route::get('goods/detail/{id?}/{entrance?}', 'GoodsController@detail'); // 商品详情
    Route::get('goods/rate/{id?}', 'GoodsController@rate'); // 商品评价
    Route::get('goods/hotkeyword', 'GoodsController@hotkeyword'); // 热门搜索

    /*
     * 店铺
     */
    Route::get('shop/lists', 'ShopController@lists'); // 店铺列表
    Route::get('shop/detail/{id?}', 'ShopController@detail'); // 店铺详情

    /**
     * 店铺装修
     */
    Route::get('shop/getTmplInfo', 'ShopController@GetTmplInfo'); // 获取店铺挂件配置
    Route::get('shop/shopCatslists', 'ShopController@ShopCatslists'); // 店铺分类列表
    Route::get('shop/shopCatsAllClassTree', 'ShopController@ShopCatsAllClassTree'); // 店铺分类树列表


    /*
     * passport
     */
    Route::post('passport/signup', 'PassportController@doRegister'); // 注册
    Route::post('passport/login', 'PassportController@login'); // 登录
    Route::post('passport/logout', 'PassportController@logout'); // 退出
    Route::get('passport/auto-login/{openid?}', 'PassportController@autoLogin'); // 自动登录
    Route::post('passport/login/creat-bind', 'PassportController@createAccount'); // 注册绑定并登录
    Route::get('passport/get-code-login', 'PassportController@sendLoginCode'); // 获取登录短信验证码
    Route::post('passport/code-login', 'PassportController@loginByCode');//短信验证码登录

    /*
     * cart
     */
    Route::get('cart/num', 'CartController@CartNum'); // 购物车数量

    /*
     * 地区
     */
    Route::get('regions/allClassTree', 'RegionsController@allClassTree'); // 地区

    /*
     * 优惠券
     */
    Route::get('coupon/lists', 'CouponController@lists');   // 优惠券列表
    Route::get('coupon/detail', 'CouponController@detail');   // 优惠券详情
    Route::get('coupon/classTab', 'CouponController@classTab');   // 优惠券分类tab

    /**
     * 访问点击
     */
    Route::post('collect/data', 'TicketsActivityController@activityCollect'); // 访问点击

    /**
     * 微信小程序
     */
    Route::post('wechatmini/openid', 'WechatMiniController@openId');//获取openid
    Route::post('wechatmini/creat-bind', 'WechatMiniController@createAccount');//绑定账号
    Route::get('wechatmini/auto-login/{code?}', 'WechatMiniController@autoLogin'); // 自动登录
    Route::get('wechatmini/get-code-login', 'WechatMiniController@sendLoginCode'); // 获取登录短信验证码
    Route::post('wechatmini/code-login', 'WechatMiniController@loginByCode');//短信验证码登录
    Route::get('wechatmini/test', 'WechatMiniController@test'); // test
    Route::post('wechatmini/push-point-change-message', 'WechatMiniController@pushPointChangeMessage'); // 推送积分变动消息给用户


    /**
     * 秒杀
     */
    Route::get('secKill/index', 'SecKillController@Sec_kill_index'); // 秒杀列表
    Route::get('secKill/getPeriods', 'SecKillController@GetPeriods'); // 展示期数,尚未开始和正在进行的
    Route::get('secKill/sec_kill_goods_list', 'SecKillController@Sec_kill_goods_list'); // 秒杀展示商品列表
    Route::get('secKill/detail', 'SecKillController@detail'); // 秒杀详情


    Route::post('allBrandShops/list', 'AllBrandsController@AllBrandShops'); // 全部品牌
    Route::get('allBrandShops/getShopFloors', 'AllBrandsController@getShopFloors'); // 商场店铺楼层
    Route::get('allBrandShops/allShopRelCatsTree', 'AllBrandsController@allShopRelCatsTree'); // 商场店铺分类

    /**
     *  团购
     */
    Route::get('group/groupList', 'GroupController@GroupList'); // 团购列表(主商品)
    Route::get('group/groupGoodList', 'GroupController@GoodsGroupList'); // 团购列表
    Route::get('group/getGoodsGroupCate', 'GroupController@GetGoodsGroupCate'); // 团购分类(当前活动存在的商品三级分类)
    Route::get('group/getUserGoodsGroup', 'GroupController@GetUserGoodsGroup'); // 发起的团购详情
    Route::get('group/goodsGroupOrderList', 'GroupController@GoodsGroupOrderList'); // 当前正在进行的拼团列表
    Route::get('group/goodsGroupOrderInfo', 'GroupController@GoodsGroupOrderInfo'); // 支付成功后获取拼团信息
    Route::get('group/groupDetail', 'GoodsController@GroupDetail'); // 跟团商品详情

    /**
     *  积分专区
     */
    Route::get('pointGoods/index', 'PointActivityController@lists'); // 积分专区商品列表
    Route::get('pointGoods/class', 'PointActivityController@classLists'); // 积分商品分类列表
    Route::get('pointGoods/index-group', 'PointActivityController@lists'); // 积分专区商品列表-牛币专用
    Route::get('pointGoods/class-group', 'PointActivityController@classLists'); // 积分商品分类列表-牛币专用


    Route::post('test/index', 'TestController@index'); // 测试
    Route::post('test/importTest', 'TestController@ImportTest');
    Route::get('test/index2', 'TestController@index2'); // 测试
    Route::get('test/index3', 'TestController@index3'); // 测试UV表


    Route::get('user/center/bottomImg', 'UserController@bottomImage'); // 获取会员中心的底部广告图
    Route::get('index/banner', 'IndexController@banner'); // 获取banner图配置
    Route::get('index/csmobile', 'IndexController@csmobile'); // 获取客服电话配置
    Route::get('index/csweixin', 'IndexController@csweixin'); // 获取客服微信



    /**
     * 主题页挂件
     */
    Route::get('index/indexWidgets', 'IndexController@IndexWidgets'); // 活动页挂件
    Route::get('index/newWidgets', 'IndexController@newIndexWidgets'); // 首页装修

    Route::get('index/showLive', 'IndexController@showLive'); // 直播

    /**
     * 集团首页模块
     */
    Route::get('self-index/newWidgets', 'GroupIndexController@newIndexWidgets'); // 甄选首页
    Route::get('self-index/getRecommend', 'GroupIndexController@getRecommend'); // 为你推荐
    Route::get('self-index/recommend-title', 'GroupIndexController@getRecommendTitle'); // 为你推荐标题
    Route::get('self-index/platformPointList', 'GroupIndexController@platformPointList'); //可兑换的牛币的项目列表



    Route::get('index/showLive', 'IndexController@showLive'); // 直播
    /**
     * 秒杀压力测试
     */
    Route::get('secKill/testSeckill', 'TestSeckillController@testSeckill'); // 秒杀压力测试-----抢购

    Route::get('secKill/getUserToken', 'TestSeckillController@GetUserToken'); // 获取会员token


    Route::get('secKill/testSecKillWaiting', 'TestSeckillController@testSecKillWaiting'); // 抢购加入购物车
    Route::get('secKill/testSeckillCreateTrade', 'TestSeckillController@testSeckillCreateTrade'); // 测试生成秒杀订单
    Route::get('secKill/showSeckill', 'TestSeckillController@ShowSeckill'); // 秒杀信息

    Route::get('secKill/testCreateTrade', 'TestSeckillController@testCreateTrade'); // 秒杀信息

    /**
     * 文章/活动
     */
    Route::get('article/lists', 'ArticleController@lists');    // 文章列表
    Route::get('article/detail/{id?}', 'ArticleController@detail');  // 文章详情

    /**
     * 模拟操作
     */
    Route::post('api-trade/commitAftersalesApplyTest', 'TradeAfterSalesController@commitAftersalesApplyTest'); // 模拟售后申请提交

    /**
     * 首页店铺切换
     */
    Route::get('index-shop/shopNearby', 'IndexController@ShopNearby'); //店铺最近距离以及列表信息
    Route::get('index-shop/shopList', 'IndexController@ShopList'); //获取店铺列表
});

Route::namespace('ShopEM\Http\Controllers\Shop\V1')->middleware('auth:api')->group(function () {

    Route::get('wechatmini/detail', 'WechatMiniController@detail'); // 用户信息
    Route::get('index-item/lists-login', 'IndexController@itemListLogin'); //集团项目列表(登录状态)

    /*
     * 用户
     */
    Route::get('user/detail', 'UserController@detail'); // 用户信息
    Route::post('user/storeAddress', 'UserController@storeAddress'); // 保存用户收货地址
    Route::post('user/updateAddress', 'UserController@updateAddress'); // 编辑用户收货地址
    Route::get('user/detailAddress/{id?}', 'UserController@detailAddress'); // 收货地址详情
    Route::get('user/addressLists', 'UserController@addressLists'); // 用户收货地址列表
    Route::get('user/deleteAddress', 'UserController@deleteAddress'); // 删除用户收货地址
    Route::post('user/modifypwd', 'UserController@modifypwd'); // 修改密码
    Route::post('user/modifyPayPwd', 'UserController@modifyPayPwd'); // 修改支付密码
    Route::post('user/modifyProfiles', 'UserController@modifyUserProfiles'); // 修改用户信息
    Route::get('user/verification/mobile', 'UserController@mobileVerification');//验证是否绑定手机号
    // Route::post('user/bind/mobile', 'UserController@bindMobile');//绑定手机号
    Route::post('user/bind/openid', 'UserController@bindOpenid');//绑定openid
    Route::get('user/check/openid', 'UserController@checkOpenid');//检查会员是否绑定openid
    Route::get('user/check/paypassword', 'UserController@checkPayPassword');//检查用户是否设置支付密码
    Route::get('user/currency/code', 'UserTradeController@getCurrencyTrades');//获取游戏币订单信息

    Route::get('user/refreshPoint', 'MemberContronller@refreshPoint'); // 刷新当前项目积分
    Route::get('user/pointExchangeLog', 'MemberContronller@pointExchangeLog'); // 积分/牛币兑换列表
    Route::get('user/refreshSelfPoint', 'MemberContronller@refreshSelfPoint'); // 刷新牛币
    Route::post('user/exchangeSelfPoint', 'MemberContronller@exchangeSelfPoint'); // 兑换牛币
    Route::get('user/setPaltform', 'MemberContronller@setPaltform'); // 设置默认项目

    Route::get('user/getPointLog', 'MemberContronller@getPointLog'); // 我的积分-积分明细
    Route::get('user/pointRule', 'MemberContronller@pointRule'); // 获取积分规则
    Route::get('user/getRule', 'MemberContronller@getRule'); // 获取规则
    Route::get('user/pointDetail', 'MemberContronller@pointDetail'); // 积分详情


    /*
     * 验证码
     */
    // Route::post('user/code/bind-mobile', 'UserController@sendCheckMobileCode');//发送绑定手机验证码
    /*
    * 收藏
    */
    Route::get('user/storeGoodsFavorite', 'FavoriteController@storeGoodsFavorite'); // 用户收藏商品
    Route::get('user/deleteGoodsFavorite', 'FavoriteController@deleteGoodsFavorite'); // 用户取消收藏商品
    Route::get('user/goodsFavoriteLists', 'FavoriteController@goodsFavoriteLists'); // 用户商品收藏列表
    Route::get('user/storeShopFavorite', 'FavoriteController@storeShopFavorite'); // 用户收藏店铺
    Route::get('user/deleteShopFavorite', 'FavoriteController@deleteShopFavorite'); // 用户取消收藏店铺
    Route::get('user/shopFavoriteLists', 'FavoriteController@shopFavoriteLists'); // 用户店铺收藏列表

    // 会员订单trade
    Route::get('user/trade/lists', 'UserTradeController@lists'); // 用户订单列表
    Route::get('user/trade/detail', 'UserTradeController@detail'); // 用户订单详情
    Route::get('user/trade/order/detail', 'UserTradeController@orderDetail'); // 用户订单详情
    Route::get('user/trade/tab', 'UserTradeController@tab'); // 用户订单统计
    Route::get('user/trade/cancel/lists', 'UserTradeController@cancelLists'); // 用户取消订单列表
    Route::get('user/trade/afterSales/lists', 'UserTradeController@afterSalesLists'); // 用户售后订单列表

    Route::get('user/trade/getLogisticsInfo', 'UserTradeController@GetLogisticsInfo'); // 根据订单获取物流轨迹

    /*
     * 购物车
     */
    Route::post('cart/add', 'CartController@store'); // 加入购物车
    Route::get('cart/lists', 'CartController@lists'); // 购物车信息
    Route::post('cart/changeNum', 'CartController@changeGoodsNum'); // 修改购物车商品数量
    Route::post('cart/delete', 'CartController@delGoods'); // 删除购物车中的商品
    Route::post('cart/updateSelected', 'CartController@updateSelected'); // 更新购物车选中商品
    Route::post('cart/platformCoupon', 'CartController@getUserPlatformCouponList'); // 获取可用的平台券
    Route::post('cart/selectedAct', 'CartController@selectedAct'); // 选择活动
    Route::post('cart/userActList', 'CartController@getUserActList'); // 获取可用的店铺活动
    Route::post('cart/shopActList', 'CartController@getShopActList'); // 获取可用的店铺活动

    /*
     * check order
     */
    Route::get('checkOrder/lists', 'CheckOrderController@lists'); // 结算选中商品列表
    Route::get('checkOrder/ztLists/{shop_id?}', 'CheckOrderController@zitiLists'); // 自提地址列表
    Route::get('checkOrder/reckonPoint', 'CheckOrderController@reckonPoint'); // 判断是否可以使用积分抵扣

    /*
     * trade
     */
    Route::post('trade/create', 'TradeUserController@create'); // 创建订单
    Route::post('payment/updatePayApp', 'PaymentController@updatePayApp'); // 更新支付方式
    Route::get('payment/info', 'PaymentController@paymentInfo'); // 支付单信息

    Route::get('payment/paycenter', 'PaymentController@paycenter'); //  获取支付方式列表
    Route::post('payment/generate', 'PaymentController@generate'); //  请求支付网关
    Route::get('payment/list', 'PaymentController@payList');//支付方式列表

    Route::post('trade/cancel', 'TradeUserController@tradeCancelCreate'); // 取消订单
    Route::post('trade/receipt', 'TradeUserController@confirmReceipt'); // 确认收货

    Route::get('trade/ship-info', 'UserTradeController@getShipInfo'); // 订单物流信息

    Route::post('payment/paystatus', 'PaymentController@apiPayStatus'); //  获取支付方式列表


    /*
     *  aftersales
     */

    Route::post('aftersales/aftersalesApply', 'TradeAfterSalesController@aftersalesApply'); // 订单展示退换货信息
    Route::post('aftersales/commitAftersalesApply', 'TradeAfterSalesController@commitAftersalesApply'); //  提交售后申请
    Route::get('aftersales/detail', 'TradeAfterSalesController@detail'); //  售后订单详情
    Route::post('afterrefund/apply', 'TradeAfterRefundController@apply'); //  退款申请

    Route::post('aftersales/cancelAftersalesApply', 'TradeAfterSalesController@cancelAftersalesApply'); //  撤销售后申请


    Route::get('trade/Chooeslogistics', 'TradeUserController@Chooeslogistics'); // 获取物流信息


    Route::post('aftersales/sendback', 'TradeAfterSalesController@sendback'); // 订单回寄物流信息


    /*
     * 优惠券
     */
    Route::get('coupon/lists-login', 'CouponController@listsLogin');   // 优惠券列表(已登录)
    Route::get('user/coupon', 'CouponController@userList');   // 用户优惠券列表
    Route::get('user/coupon/detail/{id?}', 'CouponController@userCouponDetail');   // 已领优惠券详情
    Route::post('coupon/receive', 'CouponController@receive');   // 领取优惠券
    Route::get('coupon/detail/{id?}', 'CouponController@detail');   // 优惠券详情
    Route::get('coupon/offline/{bn?}', 'CouponController@offLineCoupon');   // 线下优惠券领取页面

    /*
     * 活动
     */
    Route::get('Activity/lists', 'ActivityController@lists');   // 活动列表
    Route::get('Activity/detail/{id?}', 'ActivityController@detail');   // 活动详情

    /*
     * rate
     */
    Route::post('rate/add', 'RateController@createRate'); // 添加评价
    Route::get('rate/lists', 'RateController@lists'); // 评价列表
    Route::post('rate/update', 'RateController@updateRate'); // 编辑评价
    Route::post('rate/append/create', 'RateController@appendRate'); // 追加评价

    /*
     * test
     */
    Route::post('user/test', 'PassportController@test'); // 用户测试

    /*
     * Album
     */
    Route::post('upload/image', 'UploadController@image'); // 上传图片

    /**
     * 秒杀
     */
    Route::post('secKill/secKillStore', 'SecKillController@SecKillStore'); // 处理秒杀商品,进入抢购
    Route::get('secKill/secKillWaiting', 'SecKillController@SecKillWaiting'); // 秒杀等待页面
    Route::get('secKill/test', 'SecKillController@test'); // test


    /**
     * 会员卡
     */
    Route::get('userCard/lists', 'UserCardController@lists');//会员卡列表
    Route::get('user/platform/default', 'MemberContronller@defaultPaltform');//默认项目

    /**
     * 会员活动
     */
    Route::get('member-activity/lists', 'MemberActivityController@lists');//活动列表
    Route::get('member-activity/detail', 'MemberActivityController@detail');//活动详情
    Route::get('member-activity/apply', 'MemberActivityController@apply');//活动详情


    Route::get('user/getSubscribeTemplate', 'UserController@GetSubscribeTemplate');  // 获取微信服务消息模板(单个)
    Route::get('user/team','UserController@team');  // 我的队友
    /**
     * 申请成为推广员模块
     */
    Route::get('user/applyCheck','PromoterController@applyCheck');  // 判断会员状态
    Route::post('user/applyAction','PromoterController@applyAction');  // 提交申请
    Route::get('user/check-verified','PromoterController@checkVerified');  // 检查是否实名认证

    Route::get('user/goodsSpreadLists','PromoterController@goodsSpreadLists');  // 获取所有推物信息列表
    Route::get('user/relatedLogsList','PromoterController@RelatedLogsList');  // 获取推广绑定关系列表
    Route::get('user/getPersonDistribution','PromoterController@GetPersonDistribution');  // 个人推广信息
    Route::get('user/getWxMiniQr','PromoterController@GetWxMiniQr');  // 推广员生成个人小程序二维码
    Route::get('user/getPromoterInfo','PromoterController@GetPromoterInfo');  // 推广员信息(二维码)


    /**
     * 合伙人
     */
    Route::get('user/creatPartnerWxMiniQr','PromoterController@CreatPartnerWxMiniQr');  // 生成分销商推广二维码
    Route::get('user/getPartnerInfo','PromoterController@GetPartnerInfo');  // 分销商信息
    Route::get('user/getPartnerDistribution','PromoterController@GetPartnerDistribution');  // 小店销售看板
    Route::get('user/getPartnerDistributorCenter','PromoterController@GetPartnerDistributorCenter');  // 分销商看板
    Route::get('user/checkPartner','PromoterController@CheckPartner');  // 小店二维码判断是否允许申请
    Route::get('user/promoterList','PromoterController@PromoterList');  // 小店合伙人

    /**
     * 会员福利
     */
    Route::get('member/benefits/point', 'MemberBenefitsController@newPointStatus');// 新会员送积分
    Route::get('member/benefits/getPoint', 'MemberBenefitsController@getPoint');// 新会员领取赠送积分


    //-----------------抽奖模块start--------------
    /**
     * 会员抽奖相关
     */
    Route::get('lottery/record/luckDraw','LotteryRecordController@luckDraw'); // 会员抽奖
    Route::get('lottery/record/list','LotteryRecordController@list');  // 会员中奖信息

    Route::post('user/share','UserController@userShare');  // 会员分享

    /**
     * 抽奖情况相关
     */
    Route::get('lottery/all','LotteryRecordController@lotteryAll'); // 奖项信息
    Route::get('lottery/detail','LotteryRecordController@lotteryDetail'); // 抽奖规则
    Route::get('lottery/getDetail/{id?}','LotteryRecordController@getLotteryDetail'); // 根据活动ID获取抽奖规则
    Route::get('lottery/record/show','LotteryRecordController@list'); # 展示中奖记录

    /**
     * 奖品相关
     */
    Route::get('reward/rewardUserList','ActivitiesRewardController@RewardUserList');  // 会员获奖列表
    Route::get('reward/rewardUserDetail','ActivitiesRewardController@RewardUserDetail');  // 会员获奖明细
    Route::post('reward/rewardCreateTrade','ActivitiesRewardController@RewardCreateTrade');  // 会员领奖(生成订单
    //-----------------抽奖模块模块end----------------

    //-----------------自助积分模块start--------------
    Route::get('self-help-integral/lists','IntegralBySelfController@lists');  // 申请列表
    Route::post('self-help-integral/upload','IntegralBySelfController@upload');  // 拍照上传
    Route::get('self-help-integral/detail/{id?}','IntegralBySelfController@detail');  // 记录明细
    //-----------------自助积分模块end----------------
    Route::get('user/partnerRelated','PromoterController@PartnerRelated');  // 推广员上级小店


    Route::get('user/applyRecordLists','PromoterController@ApplyRecordLists');  // 申请记录列表
    Route::get('user/applyRecordDetail','PromoterController@ApplyRecordDetail');  // 审核申请详情
    Route::post('user/checkerExamine','PromoterController@CheckerExamine');  // 审核(推广员,小店)

    /**
     * 商品分销推广
     */
    Route::get('goods/createWxMiniQr','GoodsController@CreateWxMiniQr');  //生成个人小程序商品二维码
    Route::get('goods/getWxMiniGoodsPerson','GoodsController@GetWxMiniGoodsPerson');  //获取个人分享商品信息
    Route::get('goods/setGoodsRelated','GoodsController@SetGoodsRelated');  //推广商品关联会员关系
    //订单
    Route::get('trade/tradeEstimatesLists','PromoterController@TradeEstimatesLists');  //会员推广订单列表(预估收益)
    Route::get('trade/estimatesOrderLists','PromoterController@EstimatesOrderLists');  //会员推广订单明细列表(预估收益)

    /**
     * 余额提现
     */
    Route::get('deposit/getUserDetail','PromoterController@GetUserDetail');  // 会员提现详情
    Route::post('deposit/applyCashOut','PromoterController@ApplyCashOut');  // 会员申请提现
    Route::get('deposit/userDepositCashesList','PromoterController@UserDepositCashesList');  // 会员个人申请提现列表
    Route::get('deposit/getUserApplyDetail','PromoterController@GetUserApplyDetail');  // 会员提现申请详情
    Route::get('deposit/applyCashOutCheck','PromoterController@ApplyCashOutCheck');  // 检查申请提现状态
    Route::get('deposit/department','PromoterController@allShowDepartment');  // 所有可见部门

    Route::post('user/share','UserController@userShare')->name('lottery.record.list');  // 会员分享

    Route::get('goods/setRelated','GoodsController@SetRelated');  //推广关联会员关系

    Route::get('user/getSubscribe','UserController@getSubscribe');  // 服务通知

    /**
    * 店铺回寄地址模块start
     */
    Route::get('user/trade/afterSales/send-back-addr/{shop_id?}', 'TradeAfterSalesController@sendBackAddr'); // 获取回寄地址
    Route::post('user/trade/afterSales/confrim-after', 'TradeAfterSalesController@confrimAfter'); // 售后确认
    /**
     * 钱包功能
     */
    Route::get('user/wallet/sendCode','UserWalletController@sendCode');  // 钱包功能发送验证码
    Route::get('user/wallet/checkCode','UserWalletController@checkCode');  // 检验验证码
    Route::get('user/wallet/hasPayPassword','UserWalletController@hasPayPassword');  // 是否设置支付密码
    Route::post('user/wallet/setPayPassword','UserWalletController@setPayPassword');  // 设置支付密码
    Route::get('user/wallet/checkPayPassword','UserWalletController@checkPayPassword');  // 检验密码
    Route::get('user/wallet/paycode','UserWalletController@openPayCode');  // 显示支付码
    Route::get('user/wallet/notify','UserWalletController@notifyStatus');  // 支付码支付状态

    /**
     * 钱包---mssjxzw
     */
    Route::get('wallet/info', 'WalletController@memberInfo');
    Route::get('wallet/register', 'WalletController@registerMember');
    Route::post('wallet/bind', 'WalletController@bindCard');
    Route::get('wallet/physical/list', 'WalletController@physicalList');
    Route::post('wallet/pay/{app}', 'WalletController@payOnline');
    Route::get('wallet/history', 'WalletController@history');



    /**
     * 切换店铺
     */

    Route::get('index-shop/shopListLogin', 'IndexController@ShopListLogin'); //登录状态记录选择的店铺





});


