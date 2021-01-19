<?php
/**
 * ClassUpdateTest.php
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;


class ClassUpdateTest extends TestCase
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

        $class_map = DB::table('class_map')->get()->toArray();
        $goods_classes = DB::table('goods_classes')->get()->toArray();
        $goods_classes = array_column($goods_classes,null,'id');
        foreach ($class_map as $key => $value) 
        {
        	$value = (array)$value;
        	$gc_2 = $value['gc_2'];
        	$gc_1 = isset($goods_classes[$gc_2]) ? $goods_classes[$gc_2]->parent_id : 0;
        	DB::table('class_map')->where('ego_gc_3',$value['ego_gc_3'])->update(['gc_1'=>$gc_1]);
        }
    }
}