<?php
/**
 * CheckSellerTest.php
 */
namespace Tests\Unit\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;


class CheckSellerTest extends TestCase
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
        $egoSellerList = $this->egoSellerList();
        $sellerList = $this->sellerList();
        $check = [];
        foreach ($egoSellerList as $key => $value) 
        {
        	if (in_array($value,$sellerList)) {
        	 	$check[] = $value;
        	 } 
        }
        testLog(['check-seller'=>$check]);
    }


    public function sellerList()
    {
    	$insert = [];
    	$seller = DB::table('seller_accounts_on')->get()->toArray();
    	foreach ($seller as $key => $value) 
    	{
    		$value = (array)$value;
    		$insert[] = $value['username'];
    	}
    	return $insert;
    }


    public function egoSellerList()
    {
    	$insert = [];
    	$seller = DB::connection("mysql_master")->table('seller_accounts_on')->get()->toArray();
    	foreach ($seller as $key => $value) 
    	{
    		$value = (array)$value;
    		$insert[] = $value['username'];
    	}
    	return $insert;
    }
}