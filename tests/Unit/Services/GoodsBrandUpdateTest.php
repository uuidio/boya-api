<?php

/**
 * GoodsBrandUpdateTest.php
 * @Author: nlx
 * @Date:   2020-07-16 13:49:05
 * @Last Modified by:   nlx
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;


class GoodsBrandUpdateTest extends TestCase
{

	/**
     * A basic unit test example.
     *
     * @return void
     */
    public function testExample()
    {
    	ini_set('memory_limit', '-1');
        set_time_limit(0);

        $brand_map = DB::table('brand_map')->get()->toArray();
    	$brand_map = array_column($brand_map,null,'ego_brand_id');

        $goods_data = DB::table('goods')->where('gm_id',21)->get()->toArray();

        foreach ($goods_data as $key => $value) 
        {
        	$value = (array)$value;
        	$brand_id = $value['brand_id'];
        	$brand_id = isset($brand_map[$brand_id]) ? $brand_map[$brand_id]->brand_id : 0;
        	DB::table('goods')->where('id',$value['id'])->update(['brand_id'=>$brand_id]);
        }
    }
}