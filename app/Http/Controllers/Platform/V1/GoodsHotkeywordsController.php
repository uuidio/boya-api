<?php
/**
 * @Filename        GoodsHotkeywordsController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Models\GoodsHotkeywords;
use ShopEM\Repositories\GoodsHotkeywordsRepository;
use ShopEM\Http\Requests\Platform\GoodsHotkeywordsRequest;

class GoodsHotkeywordsController extends BaseController
{

    /**
     * 热门搜索关键字列表
     *
     * @Author djw
     * @param GoodsHotkeywordsRepository $repository
     * @return mixed
     */
    public function lists(GoodsHotkeywordsRepository $repository, Request $request)
    {
        $data = $request->all();
        $data['gm_id'] = $this->GMID;
        $lists = $repository->listItems();

        if (empty($lists)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->listShowFields(),
        ]);
    }

    /**
     * 关键字详情
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

        $detail = GoodsHotkeywords::find($id);

        if(empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 添加关键字
     *
     * @Author djw
     * @param GoodsHotkeywordsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(GoodsHotkeywordsRequest $request)
    {
        $data = $request->only('keyword', 'listorder', 'disabled');

        //检测是否已存在该关键字
        $goodsService = new \ShopEM\Services\GoodsService;
        $check = $goodsService->hotKeywordCheck($data['keyword'], false, $this->GMID);
        if(!$check) {
            return $this->resFailed(701, '关键字已存在');
        }
        $data['gm_id'] = $this->GMID;
        GoodsHotkeywords::create($data);
        return $this->resSuccess();
    }

    /**
     * 更新关键字
     *
     * @Author djw
     * @param GoodsHotkeywordsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(GoodsHotkeywordsRequest $request)
    {
        $id = intval($request->id);
        if($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('keyword', 'listorder', 'disabled');

        $goodsHotkeywords = GoodsHotkeywords::find($id);
        if(empty($goodsHotkeywords)) {
            return $this->resFailed(701);
        }

        //检测是否已存在该关键字
        $goodsService = new \ShopEM\Services\GoodsService;
        $check = $goodsService->hotKeywordCheck($data['keyword'], $id , $this->GMID);
        if(!$check) {
            return $this->resFailed(701, '关键字已存在');
        }

        $goodsHotkeywords->update($data);

        return $this->resSuccess();
    }

    /**
     * 删除关键字
     *
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if($id <= 0) {
            return $this->resFailed(414);
        }

        try {
            GoodsHotkeywords::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        return $this->resSuccess();
    }
}
