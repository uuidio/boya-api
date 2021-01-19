<?php


namespace ShopEM\Http\Controllers\Group\V1;


use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Http\Requests\Group\OpenApiEntryRequest;
use ShopEM\Models\OpenapiAuth;
use ShopEM\Repositories\OpenApiAuthRepository;

class OpenApiController extends BaseController
{
    public function entry(OpenApiEntryRequest $request)
    {
        $data = $request->only('name', 'api_auth', 'gm_auth', 'start', 'end', 'status');
        try {
            $data['appid'] = getRandStr(20);
            $data['secret'] = getRandStr(64);
            OpenapiAuth::create($data);
        } catch (\Exception $exception) {
            $this->adminlog('开放接口appid生成失败',0);
            return $this->resFailed(500, '开放接口appid生成失败');
        }
        $this->adminlog('生成开放接口appid:'.$data['appid']);
        return $this->resSuccess();
    }

    public function Lists(Request $request,OpenApiAuthRepository $repository)
    {
        $data = $request->only('name','api_auth','gm_auth','appid');
        return $this->resSuccess([
            'lists' => $repository->search($data),
            'field' => $repository->listFields(),
        ]);
    }

    public function change(Request $request)
    {
        $data = $request->only('api_auth','gm_auth','status','start','end');
        if (!$request->filled('id')) return $this->resFailed(414,'缺少参数');
        $detail = OpenapiAuth::find($request->id);
        if (empty($detail)) {
            return $this->resFailed(414,'没有此数据');
        }
        try {
            if ($data) {
                foreach ($data as $key => $value) {
                    $detail->$key = $value;
                }
                $detail->save();
            }
            $this->adminlog('开放接口appid修改资料成功',1);
            return $this->resSuccess();
        } catch (\Exception $exception) {
            $this->adminlog('开放接口appid修改资料失败',0);
            return $this->resFailed(500,'更新失败');
        }
    }

    public function del()
    {
        $id = request('id', 0);
        if ($id <= 0) return $this->resFailed(414, '缺少参数');

        $row = OpenapiAuth::find($id);
        if (empty($row)) return $this->resFailed(414, '无此数据');

        try {
            $row->delete();
            $this->adminlog('删除开放接口appid成功',1);
            return $this->resSuccess();
        } catch (\Exception $exception) {
            $this->adminlog('删除开放接口appid失败',0);
            return $this->resFailed(500,'删除失败');
        }
    }

    public function fetchApiAuthList()
    {
        return $this->resSuccess(config('openapi'));
    }
}
