<?php
/**
 * @Filename RejectMsgController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author         zhp <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Http\Requests\Platform\RejectMsgRequest;
use ShopEM\Models\RejectMsg;
use ShopEM\Repositories\RejectMsgRepository;


class RejectMsgController extends BaseController
{
    /**
     * 消息列表
     *
     * @Author zhp <mo@mocode.cn>
     * @param Request $request，RejectMsgRepository $rejectMsgRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request,RejectMsgRepository $rejectMsgRepository){
        $data = $request->all();
        $lists = $rejectMsgRepository->search($data, 1);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        foreach ($lists as $key => $value) {

            $lists[$key]['reject_status'] = $value['reject_status'] ? '显示' : '隐藏';

        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $rejectMsgRepository->listShowFields(),
        ]);
    }
    /**
     * 消息详情
     *
     * @Author zhp <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id = 0)
    {
        $id = intval($id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $detail = RejectMsg::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }


        return $this->resSuccess($detail);
    }

    /**
     * 更新消息数据
     *
     * @Author zhp <mo@mocode.cn>
     * @param RejectMsgRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(RejectMsgRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }


        $data = $request->only('term', 'reject_status', 'reject_sort');


        $rejectMsg = RejectMsg::find($id);
        if (empty($rejectMsg)) {
            return $this->resFailed(701);
        }
        $msg_text = "更新驳回消息数据";
        try {

            $rejectMsg->update($data);

        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog( $msg_text, 1);
        return $this->resSuccess();
    }

    /**
     * 删除消息
     *
     * @Author zhp <mo@mocode.cn>
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $msg_text = "删除消息数据";
        try {
            RejectMsg::destroy($id);
        } catch (\Exception $e) {
            //日志
            $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage());
        }
        //日志
        $this->adminlog($msg_text, 1);
        return $this->resSuccess();
    }
    /**
     * 添加消息
     *
     * @Author zhp
     * @param RejectMsgRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createRejectMsg(RejectMsgRequest $request)
    {
        $request = $request->only('term', 'reject_status', 'reject_sort');

        try {
            $rejectMsg = new RejectMsg();
            $rejectMsg->term = $request['term'];
            $rejectMsg->reject_status = $request['reject_status'] ;
            $rejectMsg->reject_sort = $request['reject_sort'] ;
            $rejectMsg->save();
        } catch (\Exception $e) {
            //日志
            $this->adminlog("消息添加", 0);
            return $this->resFailed(702, $e->getMessage());
        }

        //日志
        $this->adminlog( "消息添加", 1);

        return $this->resSuccess();
    }

}
