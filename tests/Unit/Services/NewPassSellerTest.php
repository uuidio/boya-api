<?php
/**
 * NewPassSellerTest.php
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;


class NewPassSellerTest extends TestCase
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
        $sellerList = $this->sellerList();
        $check = [];
        $updateData = [];
        foreach ($sellerList as $key => $value) 
        {
        	$updateData[] = [
				'id' => $value['id'],
				'password' => bcrypt($value['new_password']),
			];
        }
        updateBatchSql('seller_accounts',$updateData);
    }

    //获取深圳场的商家
    public function sellerList()
    {
    	$insert = [];
    	$data['gm_id'] = env('EGO_GM_ID_RADIX',1);
    	$seller = DB::table('seller_accounts')->where($data)->get()->toArray();
    	foreach ($seller as $key => $value) 
    	{
    		$value = (array)$value;
    		$value['new_password'] = $value['username'].'123';
    		$insert[] = $value;
    	}
    	return $insert;
    }


}