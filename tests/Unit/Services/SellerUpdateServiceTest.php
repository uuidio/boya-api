<?php
/**
 * SellerUpdateServiceTest.php
 * 店铺迁移数据
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ShopEM\Jobs\MigratingData;

class SellerUpdateServiceTest extends TestCase
{
	protected $shop_id_radix; //切记跟商家迁移的基数相同，否则会出错

	protected $seller_id_radix;

	public function getConstruct()
    {
        $this->shop_id_radix = env('SHOP_ID_RADIX',200);
        $this->seller_id_radix = env('SELLER_ID_RADIX',200);
    }
	/**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
    	ini_set('memory_limit', '-1');
        set_time_limit(0);
        $this->getConstruct();

        DB::beginTransaction();
    	try {
    		#seller_accounts
    		$seller_data = $this->sellerList($old_seller_ids);
    		DB::table('seller_accounts')->insert($seller_data);

    		#shop_rel_sellers
    		$rel_seller_data = $this->sellerRelList($old_seller_ids);
    		DB::table('shop_rel_sellers')->insert($rel_seller_data);
    		
	    	DB::commit();
	    	$this->assertTrue(true);
	    	echo "成功";
	    	exit;
    	} catch (Exception $e) {
    		DB::rollBack();
    		$this->assertTrue(false);
    		echo "失败";
    		exit;
    	}

    }

	public function sellerList(&$old_seller_ids)
    {
    	$insert = $old_seller_ids = [];
    	$datas = DB::connection("mysql_master")->table('seller_accounts')->where('seller_type',0)->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		$old_seller_ids[] = $data['id'];
    		$data['id'] = $data['id'] + $this->seller_id_radix;
    		$data['password'] = bcrypt($data['username']);
    		$data['username'] = $data['username'];
            $data['gm_id'] = env('EGO_GM_ID_RADIX',1);
    		$insert[] = $data;
    	}
    	return $insert;
    }

    public function sellerRelList($old_seller_ids)
	{
		$insert = [];
    	$datas = DB::connection("mysql_master")->table('shop_rel_sellers')->whereIn('seller_id',$old_seller_ids)->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		unset($data['id']);
    		$data['seller_id'] = $data['seller_id'] + $this->seller_id_radix;
    		$data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;
            $data['gm_id'] = env('EGO_GM_ID_RADIX',1);
    		$insert[] = $data;
    	}
    	return $insert;
	}	
}