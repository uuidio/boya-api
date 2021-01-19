<?php
/**
 * @Filename        BaseController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Controllers\Controller;
use ShopEM\Models\SellerAccount;
use ShopEM\Models\Shop;
use ShopEM\Models\ShopRelSeller;

class BaseController extends Controller
{
    protected $shop;

    protected $GMID;

    public function __construct()
    {
        $this->shop = $this->getShop();
        $this->GMID = $this->shop->gm_id??1;
    }

    /**
     * 店铺信息
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed|null
     */
    public function getShop()
    {
        if (Auth::guard('seller_users')->check()) {

            $seller_id = Auth::guard('seller_users')->user()->id;

            return Cache::remember('cache_key_seller_id_' . $seller_id, cacheExpires(), function () use ($seller_id) {

                $seller_obj= new Shop();
                $seller=$seller_obj->leftJoin('shop_rel_sellers','shop_rel_sellers.shop_id','=','shops.id')
                    ->leftJoin('seller_accounts','seller_accounts.id','=','shop_rel_sellers.seller_id')
                    ->where(['shop_rel_sellers.seller_id'=>$seller_id])->select('shops.*')->first();

                return $seller;
            });
        }
        return null;
    }
}