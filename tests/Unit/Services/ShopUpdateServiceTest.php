<?php
/**
 * ShopUpdateServiceTest.php
 * 店铺迁移数据
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ShopEM\Jobs\MigratingData;

class ShopUpdateServiceTest extends TestCase
{
	protected $shop_id_radix; //切记跟商家迁移的基数相同，否则会出错

	protected $floors_id_radix;

	protected $rel_cat_id_radix;

	protected $templates_id_radix;

	public function getConstruct()
    {
        $this->shop_id_radix = env('SHOP_ID_RADIX',200);
        $this->floors_id_radix = env('FLOORS_ID_RADIX',100);
        $this->rel_cat_id_radix = env('REL_CAT_ID_RADIX',200);
        $this->templates_id_radix = env('TEMPLATES_ID_RADIX',200);
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
    		//商家信息关联信息
    		#shops
    		$shop_data = $this->shopList();
    		DB::table('shops')->insert($shop_data);

    		#shop_class_relations
    		$shop_class_data = $this->shopClassList();
    		DB::table('shop_class_relations')->insert($shop_class_data);
    		
    		#shop_floors
    		$floor_data = $this->floorsList();
    		DB::table('shop_floors')->insert($floor_data);
    		
    		//商家配置信息
    		#logistics_templates
    		$temp_data = $this->tempList();
    		DB::table('logistics_templates')->insert($temp_data);

    		#shop_zitis
    		$ziti_data = $this->zitiList();
    		DB::table('shop_zitis')->insert($ziti_data);

    		#shop_cats
    		$shop_cats_data = $this->shopCatsList();
    		DB::table('shop_cats')->insert($shop_cats_data);
    		
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

    // shops shop_zitis shop_floors  shop_rel_cats shop_zitis  shop_class_relations
    public function shopList()
    {
    	$insert = [];
    	$shops = DB::connection("mysql_master")->table('shops')->get()->toArray();

    	foreach ($shops as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		$data['ego_shop_id'] = $data['id'];
    		$data['id'] = $data['ego_shop_id'] + $this->shop_id_radix;
    		$data['floors_id'] = $data['floors_id'] + $this->floors_id_radix;
    		if (!empty($data['rel_cat_id_data'])) 
    		{
	    		$rel_cat_id_data = json_decode($data['rel_cat_id_data'],1);
	    		$data['rel_cat_id_data'] = json_encode($this->catIdsAdd($rel_cat_id_data));
    		}
            $data['gm_id'] = env('EGO_GM_ID_RADIX',1);

    		$insert[] = $data;
    	}
    	return $insert;
    }

    public function shopClassList()
    {
    	$insert = [];
    	$datas = DB::connection("mysql_master")->table('shop_class_relations')->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		unset($data['id']);
    		$data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;
    		$data['class_id'] = $data['class_id'] + $this->rel_cat_id_radix;
    		$insert[] = $data;
    	}
    	return $insert;
    }

    public function floorsList()
    {
    	$insert = [];
    	$datas = DB::connection("mysql_master")->table('shop_floors')->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		$data['id'] = $data['id'] + $this->floors_id_radix;
            $data['gm_id'] = env('EGO_GM_ID_RADIX',1);
    		$insert[] = $data;
    	}
    	return $insert;
    }

    public function relCatList()
    {
    	$insert = [];
    	$datas = DB::connection("mysql_master")->table('shop_rel_cats')->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		$data['id'] = $data['id'] + $this->rel_cat_id_radix;
    		$data['parent_id'] = ($data['parent_id']>0)? $data['parent_id'] + $this->rel_cat_id_radix : 0 ;
            $data['gm_id'] = env('EGO_GM_ID_RADIX',1);
    		$insert[] = $data;
    	}
    	return $insert;
    }

    public function tempList()
    {
    	$insert = [];
    	$datas = DB::connection("mysql_master")->table('logistics_templates')->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		$data['id'] = $data['id'] + $this->templates_id_radix;
    		$data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;
    		$insert[] = $data;
    	}
    	return $insert;
    }

    public function zitiList()
    {
    	$insert = [];
    	$datas = DB::connection("mysql_master")->table('shop_zitis')->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		unset($data['id']);
    		$data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;
    		$insert[] = $data;
    	}
    	return $insert;
    }

    //店铺的商品分类
    public function shopCatsList()
    {
    	$insert = [];
    	$datas = DB::connection("mysql_master")->table('shop_cats')->get()->toArray();

    	foreach ($datas as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		unset($data['id']);
    		$data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;
    		$insert[] = $data;
    	}
    	return $insert;
    }


    public function catIdsAdd(&$data)
    {
    	foreach ($data[0] as &$value) 
    	{
    		$value['value'] = $value['value'] + $this->rel_cat_id_radix;
    		$value['parent_id'] = ($value['parent_id']>0)? $value['parent_id'] + $this->rel_cat_id_radix : 0 ;

    		if (isset($value['children'])) 
    		{
    			foreach ($value['children'] as &$val) 
    			{
    				$val['value'] = $val['value']+$this->rel_cat_id_radix;
    				$val['parent_id'] = ($val['parent_id']>0)? $val['parent_id'] + $this->rel_cat_id_radix : 0 ;
    			}
    		}
    	}
    	return $data;
    }

}