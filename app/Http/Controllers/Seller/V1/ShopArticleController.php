<?php
/**
 * @Filename ShopArticleController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Repositories\ShopArticleRepository;
use ShopEM\Http\Requests\Seller\ShopArticleRequest;
use ShopEM\Models\ShopArticle;
use ShopEM\Models\AlbumPic;
use Illuminate\Support\Facades\Storage;

class ShopArticleController extends BaseController
{

    /**
     * 文章列表
     *
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request, ShopArticleRepository $shopArticleRepository)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['shop_id'] = $this->shop->id;
        $articleItems = $shopArticleRepository->search($input_data);

        if (empty($articleItems)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $articleItems,
            'field' => $shopArticleRepository->aritcleListField()
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

        $detail = ShopArticle::where('id', $id)->where('shop_id', $this->shop->id)->first();

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建文章
     *
     * @Author djw
     * @param ShopArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ShopArticleRequest $request)
    {
        $data = $request->only('title', 'cat_id', 'listorder', 'article_url', 'content', 'is_show');
        $data['cat_id'] = isset($data['cat_id']) && $data['cat_id'] ? $data['cat_id'] : 0;
        $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
        $data['content'] = isset($data['content']) && $data['content'] ? $data['content'] : '';
        $data['shop_id'] = $this->shop->id;
        $data['gm_id'] = $this->GMID;
        DB::beginTransaction();
        try {
            $article = ShopArticle::create($data);
            $local = Storage::disk('local')->url('');
            $url_pic = str_replace($local,'',$article->article_url);
            $pic = AlbumPic::where('pic_url',$url_pic)->first();
            if ($pic) {
                $pic->pic_name = $article->title.'(文章主图)';
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
     * 更新文章
     *
     * @Author djw
     * @param ShopArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ShopArticleRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('title', 'cat_id', 'listorder', 'article_url', 'content', 'is_show');

        DB::beginTransaction();
        try {
            $data['cat_id'] = isset($data['cat_id']) && $data['cat_id'] ? $data['cat_id'] : 0;
            $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
            $data['content'] = isset($data['content']) && $data['content'] ? $data['content'] : '';
            $data['shop_id'] = $this->shop->id;
            $article = ShopArticle::find($id);
            if(empty($article)) {
                return $this->resFailed(701);
            }
            $old_article_url = $article['article_url'];
            $old_name = $article->title;
            $article->update($data);

            //如果更新了图片
            if ($old_article_url != $data['article_url']) {
                $local = Storage::disk('local')->url('');
                AlbumPic::where('pic_name','like',$old_name.'%')->update(['is_use'=>0]);
                $url_pic = str_replace($local,'',$data['article_url']);
                $new = AlbumPic::where('pic_url',$url_pic)->first();
                if ($new) {
                    $new->pic_name = $data['title'].'(文章主图)';
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
     * 删除文章
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $article = ShopArticle::where('id', $id)->where('shop_id', $this->shop->id)->first();
        if(empty($article)) {
            return $this->resFailed(414);
        }

        try {
            ShopArticle::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

}