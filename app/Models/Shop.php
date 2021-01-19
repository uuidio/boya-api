<?php
/**
 * @Filename        Shop.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Shop extends Model
{
    protected $guarded = [];
    protected $shop_type_arr = ['flag' => '旗舰店', 'brand' => '品牌店', 'cat' => '类目专营店', 'store' => '多品类通用型', 'self' => '自营店'];
    protected $appends = ['seller_name', 'full_address', 'new_goods', 'floors_name', 'rel_cat_id', 'rel_cat_name', 'count_for_sale_goods', 'is_own_shop_text', 'status_text', 'shop_type_text','gm_name','promo_person','promo_good','shop_state_text'];

    function __construct()
    {
        parent::__construct();
    }


    public function getSellerNameAttribute()
    {
//        $seller = SellerAccount::where('shop_id', $this->id)->where('seller_type', 0)->first();

        $seller_obj = new Shop();
        $seller = $seller_obj->leftJoin('shop_rel_sellers', 'shop_rel_sellers.shop_id', '=', 'shops.id')
            ->leftJoin('seller_accounts', 'seller_accounts.id', '=', 'shop_rel_sellers.seller_id')
            ->where(['shop_rel_sellers.shop_id' => $this->id])->where('seller_accounts.seller_type',
                0)->select('seller_accounts.username')->first();
        if (!empty($seller->username)) {
            return $seller->username;
        }
    }

    /**
     * 商家账号
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getFullAddressAttribute()
    {
        return $this->province_name . $this->city_name . $this->area_name . $this->street_name . $this->address;
    }

    /**
     * 小区名称
     *
     * @Author moocde <mo@mocode.cn>
     * @return string
     */
//    public function getHousingNameAttribute()
//    {
//        if ($this->housing_id > 0) {
//            $housing = Housing::find($this->housing_id);
//            return empty($housing) ? '' : $housing->housing_name;
//        }
//
//        return '';
//    }

    /**
     * 反序列化店铺banner
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getShopBannerAttribute($value)
    {
         return empty($value) ? null : unserialize($value);
    }

    /**
     * 获取商家最新6个商品
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getNewGoodsAttribute()
    {
        return DB::table('goods')
            ->select('id', 'goods_name', 'goods_price', 'goods_image', 'goods_marketprice')
            ->where('shop_id', $this->id)
            ->where('goods_state', 1)
            ->orderBy('updated_at', 'desc')
            ->limit(6)
            ->get();
    }

    public function getArriveHousingAttribute($value)
    {
        // return empty($value) ? [] : unserialize($value);
    }

    public function getShopTypeTextAttribute()
    {
        return $this->shop_type_arr[$this->shop_type] ?? '';
    }

    /**
     * 序列化保存店铺banner
     *
     * @Author moocde <mo@mocode.cn>
     * @param $value
     */
    public function setShopBannerAttribute($value)
    {
        $this->attributes['shop_banner'] = empty($value) ? null : serialize($value);
    }

    public function setArriveHousingAttribute($value)
    {
        $this->attributes['arrive_housing'] = empty($value) ? null : serialize($value);
    }


    /**
     *  楼层名称
     *
     * @Author hfh_wind
     * @return string
     */

    public function getFloorsNameAttribute()
    {
        $return = ShopFloor::where(['id' => $this->floors_id])->select('name')->first();

        return empty($return) ? '' : $return['name'];
    }


    /**
     *  分类名称
     *
     * @Author hfh_wind
     * @return string
     */

    public function getRelCatNameAttribute()
    {
        $classes = [];
        if ($this->shop_type == 'brand') {
            $model = new ShopClassRelations();
            $lists = $model->select('shop_rel_cats.cat_name')->leftJoin('shop_rel_cats', 'shop_rel_cats.id', '=', 'shop_class_relations.class_id')->where('shop_class_relations.shop_id', $this->id)->get();
            if ($lists) {
                foreach ($lists as $class) {
                    $classes[] = $class['cat_name'];
                }
            }
        }
        return $classes;
    }


    /**
     *  分类id
     *
     * @Author hfh_wind
     * @return string
     */

    public function getRelCatIdAttribute()
    {
        $classes = [];
        if ($this->shop_type == 'brand') {
            $model = new ShopClassRelations();
            $lists = $model->select('class_id')->where('shop_id', $this->id)->get();
            if ($lists) {
                foreach ($lists as $class) {
                    $classes[] = $class['class_id'];
                }
            }
        }
        return $classes;
    }

    /**
     * 获取商家在售商品数量
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function getCountForSaleGoodsAttribute()
    {
        return DB::table('goods')
            ->select('id', 'goods_name', 'goods_price', 'goods_image', 'goods_marketprice')
            ->where('shop_id', $this->id)
            ->where('goods_state', 1)
            ->count();
    }

    /**
     * 保存赠送积分配置
     *
     * @Author djw
     * @param $value
     */
    public function setUserObtainPointAttribute($value)
    {
        $this->attributes['user_obtain_point'] = empty($value) ? '5|1' : implode($value, '|');
    }

    /**
     * 获取赠送积分配置
     *
     * @Author djw
     * @return mixed
     */
    public function getUserObtainPointAttribute($value)
    {
        $data = explode('|', $value);
        $config = [
            'fee' => $data[0],
            'point' => $data[1],
        ];
        return $config;
    }

    /**
     * 是否自营
     *
     * @Author djw
     * @return mixed
     */
    public function getIsOwnShopTextAttribute()
    {
        return $this->is_own_shop ? '是' : '否';
    }

    /**
     * 开店审核状态
     *
     * @Author djw
     * @return mixed
     */
    public function getStatusTextAttribute()
    {
        $text = [
            'none' => '未提交',
            'active' => '未审核',
            'locked' => '审核中',
            'successful' => '审核通过',
            'failing' => '审核驳回',
            'finish' => '开店完成',
        ];
        return $text[$this->status] ??  '';
    }

       /**
     * 追加项目名称
     * @Author swl 2020-3-12
     * @return string
     */
    public function getGmNameAttribute()
    {
        $shop_info = GmPlatform::where('gm_id', '=', $this->gm_id)->select('platform_name')->first();

        return isset($shop_info['platform_name']) ? $shop_info['platform_name'] : '';
    }

    public function getPromoPersonAttribute()
    {
        $shop_attr = ShopAttr::where('shop_id',$this->id)->first();

        return $shop_attr ? $shop_attr->promo_person : 0;
    }

    public function getPromoGoodAttribute()
    {
        $shop_attr = ShopAttr::where('shop_id',$this->id)->first();

        return $shop_attr ? $shop_attr->promo_good : 0;
    }


    /**
     * 店铺状态
     *
     * @Author djw
     * @return mixed
     */
    public function getShopStateTextAttribute()
    {
        return $this->shop_state ? '开启' : '关闭';
    }


    //获取一个pos编码
    public static function getAnShopPosCode($shop_ids=[])
    {
        $posCode = self::whereIn('id',$shop_ids)->whereNotNull('erp_posCode')->value('erp_posCode');
        return $posCode;
    }
}
