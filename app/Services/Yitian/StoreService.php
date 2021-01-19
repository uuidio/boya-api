<?php

/**
 * StoreService.php
 * @Author: nlx
 * @Date:   2020-04-20 16:20:27
 */
namespace ShopEM\Services\Yitian;
use ShopEM\Models\CrmMasterStore;

class StoreService
{
	
	public function saveList($lists,$gm_id)
	{
		foreach ($lists as $key => $value) 
		{
			$data = [];
			$data['gm_id'] = $gm_id;
			$data['storeID'] = $value['storeID']??'';
			$data['storeCode'] = $value['storeCode']??'';
			$data['storeName'] = $value['storeName']??'';
			$data['mallCode'] = $value['mallCode']??'';
			$data['typeName'] = $value['typeName']??'';
			$data['pTypeName'] = $value['pTypeName']??'';
			$store = CrmMasterStore::where(['gm_id'=>$data['gm_id'],'storeCode'=>$data['storeCode']])->first();
			if ($store) {
				$store->update($data);
			}
			if (empty($store)) {
				CrmMasterStore::create($data);
			}
		}
		return true;

	}
}