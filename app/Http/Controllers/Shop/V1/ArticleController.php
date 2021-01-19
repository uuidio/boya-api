<?php
/**
 * @Filename        ArticleController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          swl 
 */

namespace ShopEM\Http\Controllers\Shop\V1;

use Illuminate\Http\Request;
use ShopEM\Http\Controllers\Shop\BaseController;
use ShopEM\Models\Article;
use ShopEM\Models\GmPlatform;
use ShopEM\Repositories\ArticleRepository;
use Illuminate\Support\Facades\DB;

class ArticleController extends BaseController
{

    // 获取文章列表
    public function lists(ArticleRepository $repository,Request $request){
        $data = $request->all();
        $data['per_page'] = $data['per_page']??config('app.per_page');
        $data['verify_status'] = 1;//默认查看审核通过的文章
        $data['is_show'] = 1;//查看开启的文章

        if (!isset($data['gm_id'])) 
        {
            $data['gm_id'] = $this->GMID;
        }
        if (GmPlatform::gmSelf() == $data['gm_id']) {
            $data['self_show'] = 1;
            unset($data['gm_id']);
        }
        $lists = $repository->search($data);

        if (empty($lists)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess([
            'lists' => $lists,
            'field' => $repository->aritcleListField(),
        ]);
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

        $detail = Article::find($id);

        return $this->resSuccess($detail);
    } 
}