<?php
/**
 * 商品迁移数据
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;


class GoodsDataServiceTest extends TestCase
{
    protected $goods_id_radix;

    protected $shop_id_radix; //切记跟商家迁移的基数相同，否则会出错

    protected $templates_id_radix;


    public function getConstruct()
    {
        $this->goods_id_radix = env('GOODS_ID_RADIX',500);
        $this->shop_id_radix = env('SHOP_ID_RADIX',200);
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
        
    	$map_data = $this->getMapData();

    	$goods_data = $this->goodsList($map_data['class_map'],$map_data['brand_map']);
    	// $goods_ids = array_column($goods_data, 'id');

    	DB::beginTransaction();
    	try {
    		$chunkData = array_chunk($goods_data, 1000);
    		foreach ($chunkData as $data) 
    		{
                $goods_ids = [];
                $goods_ids = array_column($data, 'ego_goods_id');

    			DB::table('goods')->insert($data);
    			DB::table('goods_count')->insert($this->goodsIds($goods_ids));
    			//goods_skus
    			$goodSkuList = $this->goodSkuList($goods_ids,$map_data['class_map'],$map_data['brand_map']);
    			$chunkSkuData = array_chunk($goodSkuList, 500);
    			foreach ($chunkSkuData as $key => $skuData) 
    			{
    				DB::table('goods_skus')->insert($skuData);
    			}
    			//goods_images
                $goodImgList = $this->goodImgList($goods_ids);
                $chunkImgData = array_chunk($goodImgList, 500);
                foreach ($chunkImgData as $key => $imgData) 
                {
                    DB::table('goods_images')->insert($imgData);
                }
    		}
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

    public function getMapData()
    {
    	$class_map = DB::table('class_map')->get()->toArray();
    	$brand_map = DB::table('brand_map')->get()->toArray();
    	$class_map = array_column($class_map,null,'ego_gc_3');
    	$brand_map = array_column($brand_map,null,'ego_brand_id');
    	return ['class_map'=>$class_map,'brand_map'=>$brand_map];
    }

    // goods  goods_skus  goods_images  goods_count
    public function goodsList($class_map,$brand_map)
    {
    	$insert = [];
    	$goods = DB::connection("mysql_master")->table('goods')->get()->toArray();

    	foreach ($goods as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		$data['ego_goods_id'] = $data['id'];
    		$data['id'] = $data['ego_goods_id'] + $this->goods_id_radix;
            $data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;

            $transport_id = ($data['transport_id']>0)? $data['transport_id'] + $this->templates_id_radix : 0 ;
    		$data['transport_id'] = $transport_id ;
            
            $gc_id = $data['gc_id'];
    		$data['gc_id'] = isset($class_map[$gc_id])? $class_map[$gc_id]->gc_3 : $gc_id;
    		$data['gc_id_1'] = isset($class_map[$gc_id])? $class_map[$gc_id]->gc_1 : $data['gc_id_1'];
            $data['gc_id_2'] = isset($class_map[$gc_id])? $class_map[$gc_id]->gc_2 : $data['gc_id_2'];
            $data['gc_id_3'] = isset($class_map[$gc_id])? $class_map[$gc_id]->gc_3 : $data['gc_id_3'];
            $data['brand_id'] = isset($brand_map[$data['brand_id']])? $brand_map[$data['brand_id']]->brand_id : $data['brand_id'];
            $data['goods_shop_cid'] = null;
            $data['gm_id'] = env('EGO_GM_ID_RADIX',1);
    		$insert[] = $data;
    	}
    	return $insert;
    }
    #商品规格
    public function goodSkuList($goods_ids,$class_map,$brand_map)
    {
    	$insert = [];
    	$goods = DB::connection("mysql_master")->table('goods_skus')->whereIn('goods_id',$goods_ids)->get()->toArray();

    	foreach ($goods as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		unset($data['id']);
    		$data['goods_id'] = $data['goods_id'] + $this->goods_id_radix;
    		$data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;
    		$data['gc_id'] = isset($class_map[$data['gc_id']])? $class_map[$data['gc_id']]->gc_3 : $data['gc_id'];
    		$data['gc_id_1'] = isset($class_map[$data['gc_id']])? $class_map[$data['gc_id']]->gc_1 : $data['gc_id_1'];
    		$data['gc_id_2'] = isset($class_map[$data['gc_id']])? $class_map[$data['gc_id']]->gc_2 : $data['gc_id_2'];
    		$data['gc_id_3'] = isset($class_map[$data['gc_id']])? $class_map[$data['gc_id']]->gc_3 : $data['gc_id_3'];
    		$data['brand_id'] = isset($brand_map[$data['brand_id']])? $brand_map[$data['brand_id']]->brand_id : $data['brand_id'];
            $data['gm_id'] = env('EGO_GM_ID_RADIX',1);
    		$insert[] = $data;
    	}
    	return $insert;
    }

    #商品图片
    public function goodImgList($goods_ids)
    {
    	$insert = [];
    	$goods_images = DB::connection("mysql_master")->table('goods_images')->whereIn('goods_id',$goods_ids)->get()->toArray();

    	foreach ($goods_images as $key => $value) 
    	{
    		$data = [];
    		$data = (array)$value;
    		unset($data['id']);
    		$data['goods_id'] = $data['goods_id'] + $this->goods_id_radix;
    		$data['shop_id'] = $data['shop_id'] + $this->shop_id_radix;
    		$insert[] = $data;
    	}
    	return $insert;
    }

    public function goodsIds($goods_ids)
    {
    	$data = [];
    	foreach ($goods_ids as $key => $id) 
    	{
    		$data[$key] = ['goods_id'=>$id];
    	}
    	return $data;
    }
}
