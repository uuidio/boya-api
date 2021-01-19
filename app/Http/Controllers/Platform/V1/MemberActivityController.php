<?php
/**
 * @Filename  MemberActivityController.php
 * @Author    swl
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Requests\Platform\MemberActivityRequest;
use ShopEM\Repositories\MemberActivityRepository;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\MemberActivity;
use ShopEM\Models\AlbumPic;
use Illuminate\Support\Facades\Storage;
use ShopEM\Models\MemberActivitySku;

class MemberActivityController extends BaseController
{


    /**
     * 活动列表
     * swl 2020-4-8
     */

    public function lists(Request $request,MemberActivityRepository $repository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page']??config('app.per_page');
        $input_data['gm_id'] = $this->GMID;
        $lists = $repository->search($input_data);

        if (empty($lists)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->activityListField()
        ]);
    }


    /*
    * 添加活动
    * swl 2020-4-8
    */
    public function save(MemberActivityRequest $request){
        $data = $request->only('title','content','activity_url','type','is_show','listorder');
        
        $data['type'] =  $data['type']??0;
        $data['gm_id'] = $this->GMID;

        DB::beginTransaction();
        try {
            $activity =  MemberActivity::create($data);
            $local = Storage::disk('local')->url('');
            $url_pic = str_replace($local,'',$activity->activity_url);
            $pic = AlbumPic::where('pic_url',$url_pic)->first();
            if ($pic) {
                $pic->pic_name = $activity->title.'(活动主图)';
                $pic->is_use = 1;
                $pic->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(702, $e->getMessage());
        }
        return $this->resSuccess();     
    }

    /**
     * 更新活动
     * swl 2020-4-8
     */
    public function update(MemberActivityRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('title','content','activity_url','type','is_show','listorder');
        $data['type'] =  $data['type']??0;
        $data['verify_status'] = 0;//修改后就变活动后成待审核状态

        DB::beginTransaction();
        try {
          
            $activity = MemberActivity::find($id);
            if(empty($activity)) {
                return $this->resFailed(701);
            }
            $old_activity_url = $activity['activity_url'];
            $old_name = $activity->title;
            $activity->update($data);

            //如果更新了图片
            if ($old_activity_url != $data['activity_url']) {
                $local = Storage::disk('local')->url('');
                AlbumPic::where('pic_name','like',$old_name.'%')->update(['is_use'=>0]);
                $url_pic = str_replace($local,'',$data['activity_url']);
                $new = AlbumPic::where('pic_url',$url_pic)->first();
                if ($new) {
                    $new->pic_name = $data['title'].'(活动主图)';
                    $new->is_use = 1;
                    $new->save();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }


     /**
     *  活动详情
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

        $detail = MemberActivity::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

     /**
     * 删除活动
     * @Author swl
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $sku = MemberActivitySku::where('activity_id',$id)->first();
        if(!empty($sku)){
            return $this->resFailed(414,'该活动下有场次，无法删除！');
        }
        try {
            MemberActivity::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

}