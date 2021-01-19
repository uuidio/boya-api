<?php
/**
 * @Filename        AdminControllerr.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\AdminRequest;
use ShopEM\Models\AdminUsers;
use ShopEM\Repositories\PlatformAdminRepository;

class AdminController extends BaseController
{
    /**
     * 管理员列表
     *
     * @Author moocde <mo@mocode.cn>
     * @param PlatformAdminRepository $repository
     * @param Request                 $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(PlatformAdminRepository $repository, Request $request)
    {
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $repository->search($data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listFields,
        ]);
    }

    /**
     * 管理员详情
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail(Request $request)
    {
        $admin = AdminUsers::find(intval($request->id));

        if (empty($admin)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($admin);
    }

    /**
     * 新增管理员
     *
     * @Author moocde <mo@mocode.cn>
     * @param AdminRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(AdminRequest $request)
    {
        $data = $request->only('role_id', 'username', 'email', 'password', 'status', 'is_root');
        $data['password'] = bcrypt($data['password']);
        $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;
        $data['is_root'] = $request->has('is_root') && $data['is_root'] == 'true' ? 1 : 0;
        $data['gm_id'] = $this->GMID;
        
        if (AdminUsers::where('username', $request->username)->count() > 0) {
            return $this->resFailed(702, '用户名已经存在！');
        }

        try {
            AdminUsers::create($data);
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->resFailed(702);
        }
    }

    /**
     * 更新管理员
     *
     * @Author moocde <mo@mocode.cn>
     * @param AdminRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(AdminRequest $request)
    {
        $admin = AdminUsers::find(intval($request->id));

        if (empty($admin)) {
            return $this->resFailed(704);
        }

        if (AdminUsers::where('username', $request->username)->where('id', '!=', $admin->id)->count() > 0) {
            return $this->resFailed(702, '用户名已经存在！');
        }

        try {
            $data = $request->only('role_id', 'username', 'email', 'password', 'status', 'is_root');
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = bcrypt($data['password']);
            }
            $data['status'] = $request->has('status') && $data['status'] == 'true' ? 1 : 0;
            $data['is_root'] = $request->has('is_root') && $data['is_root'] == 'true' ? 1 : 0;

            $admin->update($data);
            return $this->resSuccess();
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            return $this->resFailed(702);
        }
    }

    /**
     * 删除管理员
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $msg_text ="删除管理员";
        try {
            $admin = AdminUsers::find(intval($request->id));
            $msg_text .= 'id:'.$request->id;
            if ($this->platform->id == $request->id) {
                return $this->resFailed(700, '不允许删除当前账号');
            }
            if (empty($admin)) {
                return $this->resFailed(700, '删除的数据不存在');
            }
            if ($admin->status > 0) {
                return $this->resFailed(700, '请先关闭启用才可删除');
            }
            $admin->delete();
            $this->adminlog($msg_text, 1);
            return $this->resSuccess();
        } catch (\Exception $exception) {
            $this->adminlog($msg_text, 0);
            Log::error($exception->getMessage());
            return $this->resFailed(600);
        }
    }
}