<?php

namespace ShopEM\Models;

use Illuminate\Database\Eloquent\Model;

class TradeWillPaymentPoint extends Model
{
    protected $guarded = [];


    public function payPoint($payment_id)
    {
    	$data = self::where(['payment_id'=>$payment_id,'status'=>0])->first();
    	if (!$data) {
    		return true;
    	}
    	try {
	    	$gm_id = $data->gm_id;
	    	$pointdata = $expdata = array(
                'user_id'  => $data->user_id,
                'order'    => json_decode($data->order,1), //v1
                'type'     => $data->type,
                'num'      => $data->num,
                'behavior' => $data->behavior,
                'remark'   => $data->remark,
                'log_type' => $data->log_type,
                'log_obj'  => $data->log_obj,
            );
	    	$yitiangroup_service = new \ShopEM\Services\YitianGroupServices($gm_id);
	    	$result = $yitiangroup_service->updateUserYitianPoint($pointdata);
	    	if ($result !== false) {
	            throw new \Exception('积分扣减失败!');
	        }
	        
	        $data->status = 1;
	        $data->save();
    	} catch (\Exception $e) {
    		throw new \Exception( $e->getMessage());
    	}
    	return true;
    }


    /**
     * [isPayPoint]
     * @param  [type]  $payment_id [description]
     * @return boolean             [description]
     */
    public function isPayPoint($payment_id)
    {
    	$exists = self::where(['payment_id'=>$payment_id,'status'=>1])->exists();
    	if ($exists) {
    		return true;
    	}
    	return false;
    }
}
