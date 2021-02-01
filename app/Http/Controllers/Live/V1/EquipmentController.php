<?php
/**
 * @Filename UploadController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          linzhe
 */

namespace ShopEM\Http\Controllers\Live\V1;

use ShopEM\Http\Controllers\Live\BaseController;
use Illuminate\Http\Request;
use ShopEM\Models\Autocue;
use ShopEM\Models\AutocueClassify;
use ShopEM\Models\TagImage;
use ShopEM\Repositories\AutocueRepository;
use ShopEM\Models\Tag;
use ShopEM\Services\Upload\UploadImage;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Notice;
use ShopEM\Models\LiveTagImage;

class EquipmentController extends BaseController
{


    /**
     * 提词器分类添加
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueClassifyAdd(Request $request)
    {
        $data = $request->only('classify_name');

        $liveId = $this->user->live_id;
        $uid = $this->user->id;
        $data['uid'] = $uid;
        $data['live_id'] = $liveId;
        AutocueClassify::create($data);

        return $this->resSuccess();
    }

    /**
     * 提词器分类列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueClassifyList(Request $request)
    {
        $data = $request->all();
        $data['per_page'] = $data['per_page']  ?? config('app.per_page');
        $liveId = $this->user->live_id;
        $uid = $this->user->id;
        $data['uid'] = $uid;
        $data['live_id'] = $liveId;

        $repository = new \ShopEM\Repositories\AutocueClassifyRepository();
        $lists = $repository->listItems($data, 10);
        if(!empty($lists)) {
            foreach($lists as $key => $value){
                $lists[$key]['count'] = Autocue::where('cid', $value['id'])->count();
            }
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 提词器分类删除
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueClassifyDel(Request $request)
    {
        DB::beginTransaction();
        try {
            $admin = AutocueClassify::find(intval($request->id));
            if (empty($admin)) {
                return $this->resFailed(700, '删除的数据不存在',(Object)[]);
            }
            $admin->delete();
            Autocue::where('cid', $request->id)->delete();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->resFailed(600);
        }

        return $this->resSuccess();
    }

    /**
     * 提词器分类编辑
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueClassifySave(Request $request)
    {
        $classify = AutocueClassify::find(intval($request->id));
        if (empty($classify)) {
            return $this->resFailed(700, '数据不存在',(Object)[]);
        }
        $classify->classify_name = $request->classify_name;
        $classify->save();

        return $this->resSuccess();
    }

    /**
     * 提词器添加
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueAdd(Request $request)
    {
        $data = $request->only('cid','title', 'antistop_one','antistop_two','antistop_three','content','sort');
        $classify = AutocueClassify::find($data['cid']);
        if(empty($classify)) {
            return $this->resFailed(701,"提词器分类不存在!",(Object)[]);
        }
        $liveId = $this->user->live_id;
        $uid = $this->user->id;
        $data['uid'] = $uid;
        $data['live_id'] = $liveId;
        $data['sort'] = $data['sort'] ?? '0';
        Autocue::create($data);

        return $this->resSuccess();
    }

    /**
     * 提词器列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueList(Request $request)
    {
        $data = $request->all();
        $data['per_page'] = $data['per_page']  ?? config('app.per_page');
        $liveId = $this->user->live_id;
        $uid = $this->user->id;
        $data['uid'] = $uid;
        $data['live_id'] = $liveId;
        $repository = new \ShopEM\Repositories\AutocueRepository();
        $lists = $repository->listItems($data, 10);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 提词器编辑
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueSave(Request $request)
    {
        $data = $request->only('id','title', 'antistop_one','antistop_two','antistop_three','content','sort');
        try
        {
            Autocue::where('id' , $data['id'])->update($data);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->resFailed(700,$msg);
        }

        return $this->resSuccess();
    }

    /**
     * 提词器删除
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocueDel(Request $request)
    {
        try {
            $admin = Autocue::find(intval($request->id));
            if (empty($admin)) {
                return $this->resFailed(700, '删除的数据不存在',(Object)[]);
            }
            $admin->delete();
        } catch (\Exception $exception) {
            return $this->resFailed(600);
        }

        return $this->resSuccess();
    }


    /**
     * 素材贴纸列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsList(Request $request)
    {
        $data = $request->all();
        $data['per_page'] = $data['per_page']  ?? config('app.per_page');
        $liveId = $this->user->live_id;
        $uid = $this->user->id;
        $data['uid'] = $uid;
        $data['live_id'] = $liveId;
        $repository = new \ShopEM\Repositories\TagsRepository();
        $lists = $repository->listItems($data, 10);
        if(!empty($lists)) {
            foreach($lists as $key => $value){
                $lists[$key]['count'] = TagImage::where('tag_id', $value['id'])->count();
            }
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 素材贴纸添加
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsAdd(Request $request)
    {
        $data = $request->only('name');

        $uid = $this->user->id;
        $data['uid'] = $uid;
        $liveId = $this->user->live_id;
        $data['live_id'] = $liveId;

        Tag::create($data);

        return $this->resSuccess();
    }

    /**
     * 素材贴纸删除
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsDel(Request $request)
    {
        DB::beginTransaction();
        try {
            $admin = Tag::find(intval($request->id));
            if (empty($admin)) {
                return $this->resFailed(700, '删除的数据不存在',(Object)[]);
            }
            $admin->delete();
            TagImage::where('tag_id', $request->id)->delete();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->resFailed(600);
        }

        return $this->resSuccess();
    }

    /**
     * 素材贴纸编辑
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagSave(Request $request)
    {
        $tag = Tag::find(intval($request->id));
        if (empty($tag)) {
            return $this->resFailed(700, '数据不存在',(Object)[]);
        }
        $tag->name = $request->name;
        $tag->save();

        return $this->resSuccess();
    }

    /**
     * 素材贴纸图片添加
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsImageAdd(Request $request)
    {
        $data = $request->only('tag_id');

        $tag = Tag::find($data['tag_id']);
        if(empty($tag)) {
            return $this->resFailed(701,"贴纸分类不存在!",(Object)[]);
        }
        $uploadImage = new UploadImage($request);
        unset($data['image']);
        $res = $uploadImage->save();
        if(isset($res['code']) && $res['code'] > 0) {
            return $res;
        }

        $uid = $this->user->id;
        $data['uid'] = $uid;
        $liveId = $this->user->live_id;
        $data['live_id'] = $liveId;
        $data['img'] = $res['result']['pic_url'];
        TagImage::create($data);

        return $this->resSuccess();
    }

    /**
     * 素材贴纸图片删除
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsImageDel(Request $request)
    {
        try {
            $admin = TagImage::find(intval($request->id));
            if (empty($admin)) {
                return $this->resFailed(700, '删除的数据不存在',(Object)[]);
            }
            $admin->delete();
        } catch (\Exception $exception) {
            return $this->resFailed(600);
        }

        return $this->resSuccess();
    }

    /**
     * 素材贴纸图片列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsImageList(Request $request)
    {
        $data = $request->all();
        $data['per_page'] = $data['per_page']  ?? config('app.per_page');
        $uid = $this->user->id;
        $data['uid'] = $uid;
        $liveId = $this->user->live_id;
        $data['live_id'] = $liveId;

        $repository = new \ShopEM\Repositories\TagsImageRepository();
        $lists = $repository->listItems($data, 10);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 素材贴纸图片保存
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tagsImageStatusSave(Request $request)
    {
        $data = $request->only('img_id','img','location','select','id');
        $liveId = $this->user->live_id;
        $data['live_id'] = $liveId;
        $isTagImgge = TagImage::find($data['img_id']);
        if (empty($isTagImgge)) {
            return $this->resFailed(700, '数据不存在');
        }
        if($data['select'] == '1'){
            unset($data['select']);
            unset($data['id']);
            LiveTagImage::create($data);
        }elseif ($data['select'] == '2'){
            $admin = LiveTagImage::find(intval($request->id));
            $admin->img = $data['img'];
            $admin->location = $data['location'];
            $admin->save();
        }else{
            $admin = LiveTagImage::find(intval($request->id));
            $admin->delete();
        }

        return $this->resSuccess();
    }

    /**
     * 选择素材贴纸图片列表
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveTagsImageList(Request $request)
    {
        $data = $request->all();
        $data['per_page'] = $data['per_page']  ?? config('app.per_page');
        $liveId = $this->user->live_id;
        $data['live_id'] = $liveId;

        $repository = new \ShopEM\Repositories\LiveTagsImageRepository();
        $lists = $repository->listItems($data, 10);
        if(!empty($lists)) {
            foreach($lists as $key => $value){
                $bkg = TagImage::where('id',$value['img_id'])->select('img')->first();
                $lists[$key]['background_img'] = $bkg['img'];
                unset($bkg);
            }
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 获取公告
     *
     * @Author linzhe
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function notice(Request $request)
    {
        $notice = Notice::orderBy('id', 'desc')->first();

        return $this->resSuccess($notice);
    }


}