<?php

/**
 * MigratingDataService.php
 * @Author: nlx
 * @Date:   2020-07-13 14:49:30
 * @Last Modified by:   nlx
 * 迁移数据更新队列
 */
namespace ShopEM\Services;

use Illuminate\Support\Facades\DB;


class MigratingDataService
{
	//更新微信信息表的会员id
	public function upWxUserInfos($ids)
	{
		$updateData = [];
		$datas = DB::table('wx_userinfos')
					->select('id','old_source_id','user_id')
					->whereIn('old_source_id',$ids)
					->get()
					->toArray();
					
		$user_datas = $this->userKeyData($ids);

		foreach ($datas as $key => $value) 
		{
			$value = (array)$value;
			$updateData[] = [
				'id' => $value['id'],
				'user_id' => isset($user_datas[$value['old_source_id']])? $user_datas[$value['old_source_id']]->id : $value['user_id'],
			];
		}

		$chunkData = array_chunk($updateData, 100);
		foreach ($chunkData as $key => $update) 
		{
			updateBatchSql('wx_userinfos',$update);
		}
		
	}

	//更新会员地址的会员id
	public function upUserAddrList($ids)
	{
		$updateData = [];
		$datas = DB::table('user_addresses')
					->select('id','old_source_id','user_id')
					->whereIn('old_source_id',$ids)
					->get()
					->toArray();

		$user_datas = $this->userKeyData($ids);

		foreach ($datas as $key => $value) 
		{
			$value = (array)$value;
			$updateData[] = [
				'id' => $value['id'],
				'user_id' => isset($user_datas[$value['old_source_id']])? $user_datas[$value['old_source_id']]->id : $value['user_id'],
			];
		}

		$chunkData = array_chunk($updateData, 100);
		foreach ($chunkData as $key => $update) 
		{
			updateBatchSql('user_addresses',$update);
		}
	}

	public function userKeyData($ids)
	{
		$user_datas = DB::table('user_accounts')
						->select('id','old_source_id')
						->whereIn('old_source_id',$ids)
						->get()
						->toArray();
		$user_datas = array_column($user_datas,null,'old_source_id');
		return $user_datas;
	}

	//维护迁移会员信息
	public function upholdWxUserInfo($user,$openid)
	{
		$old = DB::table('wx_userinfos')->where('user_id',$user->id)->where('old_source_id','>',0)->first();
		$new = DB::table('wx_userinfos')->where('openid', $openid)->first();
		if (!empty($old) && !empty($new)) 
		{
			$update['user_id'] 			= $old->user_id;
			$update['email'] 			= $old->email;
			$update['birthday'] 		= $old->birthday;
			$update['real_name'] 		= $old->real_name;
			$update['is_update_info'] 	= $old->is_update_info;

			DB::table('wx_userinfos')->where('id', $new->id)->update($update);
			DB::table('wx_userinfos')->where('id', $old->id)->delete();
		}
	}
}