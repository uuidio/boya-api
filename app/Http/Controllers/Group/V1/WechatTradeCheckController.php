<?php
/**
 * Created by lanlnk
 * @author: huiho <429294135@qq.com>
 * @Date: 2020-05-25
 * @Time: 15:07
 */

namespace ShopEM\Http\Controllers\Group\V1;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use ShopEM\Imports\WechatProjectImport;
use ShopEM\Imports\WechatTradeImport;
use ShopEM\Imports\WechatRefundImport;
use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Group\BaseController;
use ShopEM\Models\WechatTradeCheck;
use ShopEM\Services\Upload\UploadImage;
use Maatwebsite\Excel\Facades\Excel;
use ShopEM\Repositories\WechatTradeCheckRepository;



class WechatTradeCheckController extends BaseController
{
    /**
     * 微信交易数据导入
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tradeImport(Request $request)
    {
        $file  = $request->file('document');
        $fileExtension = $file->getClientOriginalExtension();
        if($fileExtension != 'xlsx'){
            return $this->resFailed(600,'上传文件格式错误，文件格式应为xlsx');
        }

        $uploadImage = new UploadImage($request);
        $filePath = $uploadImage->uploadFile_document('xlsx');

        $time = time().rand(1000,9999);
        if (!empty($filePath))
        {
            Cache::forget('1_wechat_trade_import_'.$this->groupUser->id);
            Cache::forget('2_wechat_trade_import_'.$this->groupUser->id);
            //后期可加入队列
            Excel::import(new WechatTradeImport($time,$this->groupUser->id), $filePath);
//            $a = Excel::toArray(new WechatTradeImport(), $filePath);

            $num = json_decode(Redis::get($time),true);
            Redis::del($time);

            return $this->resSuccess($num, "对账导入成功!");
        }


        return $this->resFailed(603, "上传错误");
    }


    /**获取导入的微信交易数据
     * @param WechatTradeCheck $model
     * @param WechatTradeCheckRepository $wechatTradeCheckRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTradeImportList(WechatTradeCheck $model, WechatTradeCheckRepository $wechatTradeCheckRepository)
    {
        $type = request('type',1);
        $where = Cache::get($type.'_wechat_trade_import_'.$this->groupUser->id);
        if ($where) {
            $lists = $model->whereIn('id',explode(',',$where))->get();
            if (request('download',0)) {

                $title = $wechatTradeCheckRepository->listShowFields('group');
                $return['trade']['tHeader']= array_column($title,'title'); //表头
                $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段
                $return['trade']['list']= $lists; //表头
                return $this->resSuccess($return);

            } else {

                return $this->resSuccess([
                    'lists' => $lists,
                    'field' => $wechatTradeCheckRepository->listShowFields('group'),
                ]);

            }
        } else {
            return $this->resFailed(414, '参数错误');
        }
    }



    /**
     * 微信退款数据导入
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refundImport(Request $request)
    {

        $uploadImage = new UploadImage($request);

        $filePath = $uploadImage->uploadFile_document('csv');

        if (!empty($filePath))
        {
            Excel::import(new WechatRefundImport(), $filePath);

            return $this->resSuccess([], "处理导入成功!");
        }

        return $this->resFailed(603, "上传错误");
    }


    /**
     * 项目数据导入
     * @Author RJie
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function projectImport(Request $request)
    {
        $uploadImage = new UploadImage($request);

        $filePath = $uploadImage->uploadFile_document('xlsx');

        $time = time().rand(1000,9999);
        if (!empty($filePath))
        {
            Cache::forget('1_wechat_project_import_'.$this->groupUser->id);
            Cache::forget('2_wechat_project_import_'.$this->groupUser->id);
            Excel::import(new WechatProjectImport($time,$this->groupUser->id), $filePath);

            $num = json_decode(Redis::get($time),true);
            Redis::del($time);

            return $this->resSuccess($num, "处理导入成功!");
        }

        return $this->resFailed(600,'导入失败');
    }


    /**获取导入的项目数据
     * @param WechatTradeCheck $model
     * @param WechatTradeCheckRepository $wechatTradeCheckRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProjectImportList(WechatTradeCheck $model, WechatTradeCheckRepository $wechatTradeCheckRepository)
    {
        $type = request('type',1);
        $where = Cache::get($type.'_wechat_project_import_'.$this->groupUser->id);
        if ($where) {
            $lists = $model->whereIn('id',explode(',',$where))->get();
            if (request('download',0)) {

                $title = $wechatTradeCheckRepository->listShowFields('group');
                $return['trade']['tHeader']= array_column($title,'title'); //表头
                $return['trade']['filterVal']= array_column($title,'dataIndex'); //表头字段
                $return['trade']['list']= $lists; //表头
                return $this->resSuccess($return);

            } else {

                return $this->resSuccess([
                    'lists' => $lists,
                    'field' => $wechatTradeCheckRepository->listShowFields('group'),
                ]);

            }
        } else {
            return $this->resFailed(414, '参数错误');
        }
    }


    /**
     * 线上对账列表
     * @param Request $request
     * @param WechatTradeCheckRepository $wechatTradeCheckRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request , WechatTradeCheckRepository $wechatTradeCheckRepository)
    {

        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? 15;
        //导入正常的
        $input_data['import_status'] = 1;
        $lists = $wechatTradeCheckRepository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $wechatTradeCheckRepository->listShowFields('group'),
            'count' =>  [
                'all'   => $wechatTradeCheckRepository->search(['import_status'=> 1],'',1),
                'wait_do'   => $wechatTradeCheckRepository->search(['status'=> 0, 'import_status'=> 1],'',1),
                'wechat_success'   => $wechatTradeCheckRepository->search(['status'=> 1, 'import_status'=> 1],'',1),
                'pro_success'   => $wechatTradeCheckRepository->search(['status'=> 3, 'import_status'=> 1],'',1),
                'wechat_failure'   => $wechatTradeCheckRepository->search(['status'=> 2, 'import_status'=> 1],'',1),
                'pro_failure'   => $wechatTradeCheckRepository->search(['status'=> 4, 'import_status'=> 1],'',1),
            ]
        ]);

    }


    /**
     * 导入异常列表
     * @param Request $request
     * @param WechatTradeCheckRepository $wechatTradeCheckRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function abnormalList(Request $request , WechatTradeCheckRepository $wechatTradeCheckRepository)
    {

        $input_data = $request->all();
        $input_data['per_page'] = $input_data['per_page'] ?? 15;
        //导入正常的
        $input_data['status'] = 2;
        $lists = $wechatTradeCheckRepository->search($input_data);

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $wechatTradeCheckRepository->listShowFields('group'),
        ]);

    }


    /**
     * 查看详情
     *
     */
    public function detail(Request $request)
    {
        $id = $request->id;

        if (empty($id)) {
            return $this->resFailed(414);
        }

        $trade = WechatTradeCheck::find($id);

        if (empty($trade))
        {
            return $this->resFailed(700);
        }

        return $this->resSuccess($trade);
    }




    /**
     * 删除
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request )
    {
        $input_data = $request->all();

        DB::beginTransaction(); //开启事务
        try
        {
            $ids = $input_data['id'];
            //$ids = explode(',', $input_data['id']);
            WechatTradeCheck::whereIn('id', $ids)->delete();
            DB::commit();
        }
        catch (\Exception $e)
        {
            DB::rollback();  //回滚
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }


    /**
     * 手动处理
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProcessing(Request $request)
    {
//        $input_data = $request->only('deal_status','id');
        if (!$request->filled('deal_status') || !$request->filled('id')) return $this->resFailed(414 ,"缺少参数");
        $update_data = array(
            'deal_status'   =>  request('deal_status'),
            'handler'       => 'PERSON',
            'deal_at' => date('Y-m-d H:i:s')
        );

        $friend = WechatTradeCheck::whereIn('id',request('id'))->get();

        if (count($friend) == 0) return $this->resFailed([] ,"无数据");

        $result = WechatTradeCheck::whereIn('id',request('id'))->update($update_data);

        if($result)
        {
            return $this->resSuccess([] ,"更改成功");

        }
        else
        {
            return $this->resFailed([] ,"更改失败");
        }
    }

    //---------可换导出方式----------//
    /**
     * 列表导出
     *
     */
    public function exportList(Request $request , WechatTradeCheckRepository $wechatTradeCheckRepository)
    {

        $input_data = $request->all();

        $lists = $wechatTradeCheckRepository->search($input_data , 1);
        $title = $wechatTradeCheckRepository->listFields();

        $return['goods']['tHeader']= array_column($title,'title'); //表头
        $return['goods']['filterVal']= array_column($title,'dataIndex'); //表头字段
        $return['goods']['list']= $lists; //表头

        return  $this->resSuccess($return);

    }

    /**
     * 异常导出
     *
     */
    public function exportAbnormalList(Request $request , WechatTradeCheckRepository $wechatTradeCheckRepository)
    {

        $input_data = $request->all();

        $lists = $wechatTradeCheckRepository->search($input_data , 1);
        $title = $wechatTradeCheckRepository->abnormalListFields();

        $return['goods']['tHeader']= array_column($title,'title'); //表头
        $return['goods']['filterVal']= array_column($title,'dataIndex'); //表头字段
        $return['goods']['list']= $lists; //表头

        return  $this->resSuccess($return);

    }

    //---------可换导出方式----------//

}
