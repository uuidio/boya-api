<?php

/**
 * @Author: swl
 * @Date:   2020-04-01
 */
namespace ShopEM\Http\Controllers\Group\V1;

use ShopEM\Http\Controllers\Group\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\Article;
use ShopEM\Repositories\ArticleRepository;
use Illuminate\Support\Facades\DB;

class ArticleController extends BaseController
{
	
     /**
     * 获取文章列表
     *
     * @Author swl 
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(ArticleRepository $repository,Request $request)
    {
        $data = $request->all();
        $data['per_page'] = $data['per_page']??config('app.per_page');
        $lists = $repository->search($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->aritcleListField('group'),
        ]);
    }

    /**
     * 审核平台文章
     * @Author swl 
     * @param status 1:通过 2：不通过
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request){

        $data = $request->only('id','status','remark');
        if(!isset($data['id']) || $data['id']<=0){
             return $this->resFailed(700);
        }
        if(empty($data['status'])){
            return $this->resFailed('请上传要更新的状态');
        }

        $update = [
            'verify_status'=>$data['status'],
            'verify_remark'=>$data['remark']??''
        ];
 
        try {
            Article::where('id',$data['id'])->update($update);

        } catch (\Exception $e) {

            return $this->resFailed(702, $e->getMessage());
        }
       return $this->resSuccess('更新成功！');

    }

    /**
     *  文章详情
     *
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        // $detail = Article::find($id);
         // 不需要追加属性，故不用模型查询
        $detail = DB::table('articles')->where('id',$id)->first();
        // dd($detail->activity_id);
        if (empty($detail)) {
            return $this->resFailed(700);
        }
        // 如果是活动类型，加上自定义活动名称
        if($detail->activity_id>0){
            $model = new \ShopEM\Models\CustomActivityConfig;
            $activity = $model->where('id',$detail->activity_id)->first()->toArray();
            $detail->activity_name = $activity['title'];
        }
        return $this->resSuccess($detail);
    }

}
