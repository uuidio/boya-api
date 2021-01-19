<?php

/**
 * GroupManagePlatform.php
 * @Author: nlx
 * @Date:   2020-03-03 15:42:18
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-08-18 15:05:48
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use ShopEM\Jobs\UpdateCrmStore;

use ShopEM\Repositories\GmPlatformRepository;
use ShopEM\Http\Requests\Group\AddPlatformRequest;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\AdminUsers;
use ShopEM\Models\Config;

class GroupManagePlatform extends BaseController
{
	
	public function lists(Request $request,GmPlatformRepository $repository)
	{
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? config('app.per_page');

		$lists = $repository->listItems($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
	}


	public function addPlatform(AddPlatformRequest $request)
	{
		$input = $request->only('admin_id','platform_name','address','platform_no','platform_id',
			'base_uri','app_id','secret','app_url','mini_appid','mini_secret','mch_id','pay_key','app_code','corp_code','org_code','longitude','latitude'
		);
		$adminUser = AdminUsers::where('id',$input['admin_id'])->where('is_root',1)->where('gm_id',0)->first();
		if (!$adminUser) 
		{
			return $this->resFailed(701, '该平台账号状态不可使用!');
		}
//        if (!isset($input['app_url']) || empty($input['app_url']))
//        {
//            return $this->resFailed(701, '接口地址必填!');
//        }
		
        DB::beginTransaction();
        try {
        	if (isset($request->type) && $request->type == 'self') 
        	{
	        	$typeFlag = GmPlatform::where('type', 'self')->count();
		        if ($typeFlag) {
		            return $this->resFailed(701, '集团自营类型已存在!');
		        }
        	}
        	

            $flag = GmPlatform::where('platform_name', $input['platform_name'])->count();
	        if ($flag) {
	            return $this->resFailed(701, '平台名称已经存在!');
	        }

	        $data = $input;
	        $data['type'] = $request->type??'normal';
            $data['admin_username'] = $adminUser->username;
            $gm = GmPlatform::create($data);

            $adminUser->gm_id = $gm->gm_id;
            $adminUser->save();

            $this->updateBaseConfig($gm->gm_id);
            UpdateCrmStore::dispatch(['gm_id'=>$gm->gm_id]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            // $this->adminlog("创建店铺管理账号" . $seller['username'], 0);
            return $this->resFailed(702, $e->getMessage());
        }

        $gm_id=$gm->gm_id;
        $param = Cache::rememberForever('gm_platform_'.$gm_id ,function () use ($gm_id) {
           return GmPlatform::where(['gm_id'=>$gm_id])->first();
        });

        //日志
        // $this->adminlog("创建店铺管理账号" . $seller['username'], 1);

        return $this->resSuccess($param);
	}

    /**
     * 详情
     * @Author nlx
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $id = $request->id;

        if (empty($id)) {
            return $this->resFailed(414);
        }

        $detail = GmPlatform::find($id);

        if (empty($detail))
            return $this->resFailed(700);
        
        return $this->resSuccess($detail);
    }
	/**
	 * [update 更新信息]
	 * @param  Request $request [description]
	 * @return [type]           [description]
	 */
	public function updatePlatform(Request $request)
	{
		$input = $request->only('gm_id','address','platform_name','platform_no','platform_id',
			'base_uri','app_id','secret','app_url','mini_appid','mini_secret','mch_id','pay_key','app_code','corp_code','org_code','longitude','latitude'
		);
//        if (!isset($input['base_uri']) || empty($input['base_uri']))
//        {
//            return $this->resFailed(701, '接口地址必填!');
//        }
		DB::beginTransaction();
        try {
        	if (!isset($input['gm_id'])) 
        	{
        		return $this->resFailed(701, '参数错误!');
        	}
            $gm = GmPlatform::find($input['gm_id']);
	        if (!$gm) {
	            return $this->resFailed(701, '找不到平台信息!');
	        }
            $flag = GmPlatform::where('platform_name', $input['platform_name'])->whereNotIn('gm_id',[$input['gm_id']])->count();
            if ($flag) {
                return $this->resFailed(701, '平台名称已经存在!');
            }
            GmPlatform::delGmCache($input['gm_id']);
	        unset($input['gm_id']);

	        foreach ($input as $key => $value) 
	        {
	        	$gm->$key = $value;
	        }
            $gm->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            // $this->adminlog("创建店铺管理账号" . $seller['username'], 0);
            return $this->resFailed(702, $e->getMessage());
        }

        $gm_id=$gm->gm_id;
        $cache=GmPlatform::where(['gm_id'=>$gm_id])->first();
        $param = Cache::forever('gm_platform_'.$gm_id ,$cache);

        return $this->resSuccess($param);
	}


	public function actPlatform(Request $request)
	{
		$request = $request->only('status', 'id');
        $id = $request['id'];
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        if ($request['status'] == "open") {
            $update_data['status'] = '1';
            $msg = "开启";
        } else {
            $update_data['status'] = '0';
            $msg = "关闭";
        }
        $shop = GmPlatform::find($id);
        if (empty($shop)) {
            return $this->resFailed(701);
        }
        $msg_text = $shop['platform_name'] . "项目 " . $msg;

        DB::beginTransaction();
        try
        {
        	Cache::forget('cache_gm_token_status_'.$id);
            //修改平台状态为关闭
            $shop->update($update_data);
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
	}

    /**
     * [updatePoint 更新积分设置]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updatePoint(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $shop = GmPlatform::find($id);
        if (empty($shop)) {
            return $this->resFailed(701);
        }
        $data = $request->only('open_point_exchange','use_obtain_point');
        //修改确认收货赠送积分的配置时，作相应校验
        if (isset($data['use_obtain_point'])) {
            if (!isset($data['use_obtain_point']['use_point']) || $data['use_obtain_point']['use_point'] <= 0) {
                return $this->resFailed(701, '消耗积分参数有误');
            }
            if (!isset($data['use_obtain_point']['obtain_point']) || $data['use_obtain_point']['obtain_point'] <= 0) {
                return $this->resFailed(701, '获得牛币参数有误');
            }
        }
        try {
            $msg_text = $shop->platform_name . "更新项目积分配置.".$data['open_point_exchange'];

            $shop->update($data);
        } catch (Exception $e) {
           //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage()); 
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    /**
     * [updatePoint 更新权重]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updateListOrder(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $shop = GmPlatform::find($id);
        if (empty($shop)) {
            return $this->resFailed(701);
        }
        $data = $request->only('listorder');
        
        try {
            $msg_text = $shop->platform_name . "更新项目权重为：".$data['listorder'];
            $shop->update($data);
        } catch (Exception $e) {
           //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage()); 
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }
    /**
     * [updateInfo 更新]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function updateInfo(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $shop = GmPlatform::find($id);
        if (empty($shop)) {
            return $this->resFailed(701);
        }
        $data = $request->only('allow_login');
        
        try {
            $msg_text = $shop->platform_name . " 进了更新";
            $shop->update($data);
        } catch (Exception $e) {
           //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage()); 
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }

    
	/**
	 * [updateBaseConfig 添加基础配置]
	 * @param  [type] $gm_id [description]
	 * @return [type]        [description]
	 */
	public function updateBaseConfig($gm_id)
	{
		if ($gm_id>0) 
		{
			$data = config('baseconfig');
			foreach ($data as $key => $value) 
			{
				$value['gm_id'] = $gm_id;
				$hasConfig = Config::where('gm_id',$gm_id)->where('page', $value['page'])->where('group', $value['group'])->first();
				if (!$hasConfig) 
				{
	            	Config::create($value);
				}else{
					$hasConfig->update($value);
				}
			}
			return true;
		}
		return false;
	}
}
