#GM( group_management ).md  集团版升级内容

## database 数据库统一添加集团id

	em_admin_users - 平台管理员账户表 v
	em_album_classes - 相册分类表 x 
	em_album_pics - 相册图片表 v 
	em_article_classes - 文章分类表 x 
	em_articles - 文章表 x  
	em_brands - 品牌表 v 
	em_configs - 网站配置表 v 
	em_coupons - 优惠券表 v 

	em_goods - 商品表 v 
	em_goods_attribute_values - 商品属性值表 v
	em_goods_attributes - 商品属性表 v
	em_goods_classes - 商品分类表 v
	em_goods_count - 商品相关统计表 v 
	em_goods_hotkeywords - 商品热门搜索关键字 V
	em_goods_skus - 商品sku表 v
	em_goods_specs - 商品规格表 v
	em_goods_types - 商品类型表 V

	em_groups - 拼团商品表 v
	em_payments - 支付记录表 v
	em_platform_admin_logs - 平台操作日志 v
	em_platform_roles - 平台角色表 v
	em_point_activity_goods - 积分专区商品表 v
	em_rate_traderate - 订单评价表 v
	em_sec_kill_applies - 秒杀活动规则表 v 
	em_sec_kill_applies_registers - 秒杀活动报名表 v 
	em_sec_kill_goods - 秒杀商品表 v 
	em_sec_kill_orders - 秒杀明细 v 

	em_seller_accounts - 商家账户表 v 
	em_shop_articles - 商家文章表 v
	em_shop_floors - 店铺楼层表 V 
	em_shop_rel_cats - 商场店铺分类表 V
	em_shop_rel_sellers - 商家关联店铺账号表 V
	em_shops - 店铺表 V
	em_site_configs - 网站配置表 V

	em_special_activities - 专题活动表 V
	em_stat_platform_item_statics - 平台运营商商品统计表 V
	em_stat_platform_shops - 平台店铺销售排行统计表 V
	em_stat_platform_user_orders - 平台会员下单统计表 V
	em_stat_platform_users - 平台新增会员/商家统计表 V
	em_stat_shop_item_statics - 商家商品统计表 V
	em_stat_shop_trade_statics - 商家交易统计表 V

	em_trade_activity_details - 订单的参与活动记录表 v
	em_trade_aftersales - 售后申请表 v
	em_trade_cancels - 取消订单表 v
	em_trade_day_settle_account_details - 日结订单数据表 v
	em_trade_day_settle_accounts - 日结数据表 v 
	em_trade_month_settle_accounts - 月结数据表 v
	em_trade_orders - 订单子表 v 
	em_trade_refunds - 退款申请表 v 
	em_trade_splits - 商品拆分明细表 v
	em_trades - 订单主表 v 

	em_user_deposit_cashes - 预存款提现信息表
	em_user_deposit_logs - 商城预存款记录表
	em_user_deposits - 商城预存款表
	em_user_login_logs - 会员登录日志表 x
	em_user_point_logs - 会员积分日志表
	em_user_points - 会员积分表

## database 数据库 会员资产的迁移 生成一个集团关联会员的表

	商城会员用户表
	[
		yitian_id - 益田的memberID
		yitian_point - 益田的积分
		card_type_code - 卡类型代码
		card_code -  卡号
		new_yitian_user - 益田新用户
	]

## 集团后台需要板块

``
 0，集团主账号迁移 GroupManageUser
 1，把CRM请求地址、配置信息搬到集团后台
 2，可创建平台账号
``





	





