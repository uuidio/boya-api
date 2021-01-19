<?php

/**
 * PayWalletController.php
 * @Author: nlx
 * @Date:   2020-08-10 15:31:03
 * @Last Modified by:   nlx
 */
namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Config;
use ShopEM\Models\PayWalletConfig;

class PayWalletController extends BaseController
{

	public function save(Request $request)
	{
		$status = $request->status ?? 2 ;
		$limit_shop = $request->limit_shop??[];

		DB::beginTransaction();
		try {
			if($status == 2 || empty($limit_shop))
			{
				PayWalletConfig::where('status',1)->update(['status'=>2]);
			}
			else
			{
				PayWalletConfig::where('mode','limit')->delete();
				foreach ($limit_shop as $key => $value) 
				{
					$create = [];
					$create['gm_id'] = $value['gm_id'];
					$create['status'] = 1;
					$create['limit_shop'] = implode(',', $value['shop_ids']);
					PayWalletConfig::create($create);
				}
			}

			DB::commit();
		} catch (\Exception $e) {
			DB::rollback();
            $msg = $e->getMessage();
            throw new \Exception($msg);
            
            return $this->resFailed(700, $msg);
		}
		return $this->resSuccess([],'保存成功');
	}


	public function info(Request $request)
    {
    	$limit_status = $request->limit_status??0;

        $data['status'] = PayWalletConfig::limitStatus() ? 1 : 2;
        if ($data['status'] == 1 || !empty($limit_status)) 
        {
        	$limit_shop = [];
        	$config_arr = PayWalletConfig::where('mode','limit')->get()->toArray();

        	$shop_total = 0;
        	foreach ($config_arr as $key => $config) 
        	{
        		$limit_shop[$key] = $config;
        		$shop_ids =  explode(',', $config['limit_shop']);
        		$shops =  DB::table('shops')->whereIn('id',$shop_ids)->select('shop_name','id')->get()->toArray();

        		$shop_names = array_column($shops,'shop_name');
        		$shop_ids = array_column($shops,'id');
        		
        		$limit_shop[$key]['shop_ids'] = $shop_ids;
        		$limit_shop[$key]['shop_names_text'] = implode(',', $shop_names);
        		$limit_shop[$key]['shop_names'] = $shop_names;
        		$limit_shop[$key]['shop_num'] = count($shop_names);
        		$shop_total += count($shop_names);
        	}
        	$data['platform_total'] = count($config_arr);
        	$data['shop_total'] = $shop_total;
        	$data['limit_shop'] = $limit_shop;
        }
        return $this->resSuccess($data);
    }

    protected $config_page = 'pay_wallet';

	protected $config_img_group = 'physical_img';

    public function physicalCardImg(Request $request)
    {
    	$group = $this->config_img_group;
        if($request->isMethod('get'))
        {
        	//get执行的代码
        	return $this->resSuccess(['physical_img' => $this->_keyPayWalletConfig($group)]);
	    }
	    if ($request->isMethod('post'))
	    {
	    	$physical_img = $request->physical_img ?? '';
	    	$this->_keyPayWalletConfig($group, true, $physical_img);
	        //post执行的代码
	        return $this->resSuccess(['physical_img'=>$physical_img],'保存成功');
	    }
    }


	/**
	 * 获取配置信息
	 * @param  [type]  $group  [配置分组]
	 * @param  boolean $update [是否更新]
	 * @param  string  $value  [更新的值]
	 * @return [type]          [description]
	 */
	private function _keyPayWalletConfig($group, $update=false, $value='')
	{
		$data = ['page'=>$this->config_page,'group'=>$group];
		$config = Config::where($data)->first();
		if (empty($config)) 
		{
			$data['gm_id'] = 0;
			$data['value'] = $value;
			Config::create($data);
			return $data['value'];
		}
		if ($update) 
		{
			Config::where($data)->update(['value'=>$value]);
			return $value;
		}
		return $config->value;
	}
}