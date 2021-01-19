<?php
/**
 * @Filename ArticleClassController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          djw
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Repositories\ArticleClassRepository;
use ShopEM\Traits\ListsTree;
use Illuminate\Support\Facades\Cache;
use ShopEM\Http\Requests\Platform\ArticleClassRequest;
use ShopEM\Models\ArticleClass;
use ShopEM\Services\ArticleService;
use Illuminate\Support\Facades\DB;

class ArticleClassController extends BaseController
{
    use ListsTree;
    protected $articleRepository;

    public function __construct(ArticleClassRepository $articleClassRepository)
    {
        parent::__construct();
        $this->articleClassRepository = $articleClassRepository;
    }

    /**
     * 获取全部数据
     *
     * @Author djw
     * @return mixed
     */
    public function lists()
    {
        $lists = $this->articleClassRepository->listItems();
        $lists = $this->toFormatTree($lists->toArray(), 'name');

        return $this->resSuccess([
            'lists' => $lists,
            'field' => $this->articleClassRepository->listShowFields(),
        ]);
    }


    /**
     * 文章分类树
     * @Author djw
     * @return \Illuminate\Http\JsonResponse
     */

    public function allClassTree()
    {
        $goodsClass = $this->articleClassRepository->listItems();
        if (empty($goodsClass)) {
            return $this->resFailed(700);
        }
        return $this->resSuccess($this->platformArticleClassToTree($goodsClass->toArray()));
    }

    /**
     *  文章分类详情
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

        $detail = ArticleClass::find($id);

        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建分类
     *
     * @Author djw
     * @param ArticleClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ArticleClassRequest $request)
    {
        $data =$request->only('name', 'parent_id', 'listorder');
        $data['parent_id'] = isset($data['parent_id']) && $data['parent_id'] ? $data['parent_id'] : 0;
        $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
        $data['cat_node'] = 0;
        DB::beginTransaction();
        try {
            ArticleService::classesCheckData($data);
            $class = ArticleClass::create($data);
            $data = [
                'cat_node' => implode(',', [$data['parent_id'],$class->id]),
            ];
            ArticleClass::where('id', $class->id)->update($data);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->resFailed(701, $e->getMessage());
        }

        // 清空文章分类缓存
        Cache::forget('all_article_class_tree');

        return $this->resSuccess();
    }

    /**
     * 更新文章分类
     *
     * @Author djw
     * @param ArticleClassRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ArticleClassRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data =$request->only('name', 'parent_id', 'listorder');

        try {
            $class = ArticleClass::find($id);
            if(empty($class)) {
                return $this->resFailed(701);
            }
            $data['parent_id'] = isset($data['parent_id']) && $data['parent_id'] ? $data['parent_id'] : 0;
            $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
            ArticleService::classesCheckData($data, $id);
            $data['cat_node'] = implode(',', [$data['parent_id'],$id]);
            ArticleClass::where('id', $id)->update($data);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        // 清空文章分类缓存
        Cache::forget('all_article_class_tree');
        return $this->resSuccess();
    }

    /**
     * 删除文章分类
     * @Author djw
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id = 0)
    {
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $hasChild = ArticleClass::where('parent_id', $id)->count();

        if ($hasChild > 0) {
            return $this->resFailed(701, '该分类下存在子类，无法直接删除');
        }

        try {
            ArticleClass::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }
        // 清空地区缓存
        Cache::forget('all_article_class_tree');

        return $this->resSuccess();
    }
}