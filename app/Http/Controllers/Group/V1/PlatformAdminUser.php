<?php

/**
 * PlatformAdminUser.php
 * @Author: nlx
 * @Date:   2020-03-03 11:06:48
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-03-20 17:20:59
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use ShopEM\Http\Requests\Group\AddAdminRequest;
use ShopEM\Http\Requests\Group\AdminAccountResetRequest;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\AdminUsers;
use ShopEM\Repositories\AdminUsersRepository;


class PlatformAdminUser extends BaseController
{

	public function lists(Request $request,AdminUsersRepository $repository)
	{
		$input = $request->all();
		$input['role_id'] = 1;
		$input['is_root'] = 1;

		if(isset($request->per_page)){
            $per_page = $request->per_page?$request->per_page:10;
        }else{
            $per_page = config('app.per_page');
        }

		$lists = $repository->listItems($input,$per_page);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
	}

	/**
	 * [addAdminRel 添加]
	 * @param addAdminRequest $request [description]
	 */
	public function addAdmin(AddAdminRequest $request)
	{
		$admin = $request->only('username', 'password');
        $admin['password'] = bcrypt($admin['password']);

        DB::beginTransaction();
        try {

            $flag = AdminUsers::where('username', $admin['username'])->count();
            if ($flag) {
                return $this->resFailed(701, '用户名已经存在!');
            }
            $admin['gm_id'] = 0;
            $admin['role_id'] = 1;
	        $admin['is_root'] = 1;
            AdminUsers::create($admin);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            //日志
            // $this->adminlog("创建店铺管理账号" . $seller['username'], 0);
            return $this->resFailed(702, $e->getMessage());
        }
        //日志
        // $this->adminlog("创建店铺管理账号" . $seller['username'], 1);

        return $this->resSuccess();
	}


    /**
     * 修改密码
     *
     * @Author moocde <mo@mocode.cn>
     * @param SellerAccountResetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPwd(AdminAccountResetRequest $request)
    {
        $request = $request->only('id', 'password');
        $id = $request['id'];
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        try {
            $sellerAccount = AdminUsers::find($id);
            if (empty($sellerAccount)) {
                return $this->resFailed(701, '找不到平台账号!');
            }
            $account['password'] = bcrypt($request['password']);

            AdminUsers::where(['id' => $id])->update($account);
        } catch (\Exception $e) {

            //日志
            // $this->adminlog("店铺管理账号" . $sellerAccount['username'] . "修改密码", 0);
            return $this->resFailed(701, $e->getMessage());
        }

        //日志
        // $this->adminlog("店铺管理账号" . $sellerAccount['username'] . "修改密码", 1);

        return $this->resSuccess();
    }


	/**
	 * [defend 维护之前平台的关联]
	 * @return [type] [description]
	 */
	public function defend()
	{
		try {
			$gmRel = GmPlatform::find(1);
			if ($gmRel) 
			{
				$gmRel->delete();
			}
			$admin = AdminUsers::where('role_id',1)->where('is_root',1)->first();
			$data = array(
				'gm_id' => 1,
				'admin_id' => $admin->id,
				'admin_username' => $admin->username,
				'platform_name' => '深圳益田假日广场'
			);
			GmPlatform::create($data);

		} catch (Exception $e) {
			return $this->resFailed(701);
		}
		return $this->resSuccess();
	}

    /**
     * [account_switch 平台账户开关（0禁用1启用）]
     * @Author mssjxzw
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function account_switch(Request $request)
    {
        if (!$request->filled('id') || !$request->has('status')) {
            return $this->resFailed(414, '参数不全');
        }
        $obj = AdminUsers::find($request->id);
        if(empty($obj)){
            return $this->resFailed(701, '找不到账号!');
        }
        $admin_id = $request->id;
        $gm = GmPlatform::where('admin_id',$admin_id)->first();

        $status = $request->status;
        if (empty($gm)) 
        {
            $msg_text = $obj->username." 管理账号禁用";
        }else{
            $msg_text = $gm->platform_name."项目所有管理账号禁用";
        }
        
        
        try {
            if ($status) {
                if (empty($gm)) 
                {
                    $msg_text = $obj->username." 管理账号启用";
                }else{
                    $msg_text = $gm->platform_name."项目启用超级管理员账号";
                }
                AdminUsers::where('id',$admin_id)->update(['status'=>1]);
            } else {
                if (!empty($gm)){
                    AdminUsers::where('gm_id',$gm->id)->update(['status'=>0]);
                }
            }
        } catch (\Exception $e) {
            //日志
            $this->adminlog( $msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);

        return $this->resSuccess();
    }
}
