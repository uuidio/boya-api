<?php
/**
 * @Filename ArticleController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Platform\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Http\Controllers\Platform\BaseController;
use ShopEM\Repositories\ArticleRepository;
use ShopEM\Http\Requests\Platform\ArticleRequest;
use ShopEM\Models\Article;
use ShopEM\Models\AlbumPic;
use Illuminate\Support\Facades\Storage;

class ArticleController extends BaseController
{
    protected $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        parent::__construct();
        $this->articleRepository = $articleRepository;
    }

    /**
     * 文章列表
     *
     * @Author moocde <mo@mocode.cn>
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['gm_id'] = $this->GMID;
        $articleItems = $this->articleRepository->search($input_data);

        if (empty($articleItems)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $articleItems,
            'field' => $this->articleRepository->aritcleListField()
        ]);
    }

    /**
     * [manageList 文章管理列表]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function manageList(Request $request)
    {
        $input_data = $request->all();
        $input_data['per_page'] = config('app.per_page');
        $input_data['not_self'] = $this->GMID;
        $input_data['is_show'] = 1;
        $input_data['verify_status'] = 1;
        $articleItems = $this->articleRepository->search($input_data);

        if (empty($articleItems)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $articleItems,
            'field' => $this->articleRepository->aritcleListField()
        ]);

    }

    /**
     * [manageAct 启用文章]
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function manageAct(Request $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }
        $article = Article::find($id);
        if (empty($article)) {
            return $this->resFailed(701);
        }
        $data = $request->only('self_show');
        
        try {
            // $msg_text = $shop->platform_name . " 进了更新";
            $article->update($data);
        } catch (Exception $e) {
           //日志
            // $this->adminlog($msg_text, 0);
            return $this->resFailed(701, $e->getMessage()); 
        }
        //日志
        // $this->adminlog($msg_text, 1);
        return $this->resSuccess();
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
        $detail = DB::table('articles')->where('id',$id)->where('gm_id',$this->GMID)->first();
        if (empty($detail)) {
            return $this->resFailed(700);
        }

        return $this->resSuccess($detail);
    }

    /**
     * 创建文章
     *
     * @Author djw
     * swl 改 2020-4-1
     * @param ArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ArticleRequest $request)
    {
        $data = $request->only('title','listorder', 'article_url', 'content', 'is_show','subtitle','title_is_show','activity_id','type');
        $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
        $data['content'] = isset($data['content']) && $data['content'] ? $data['content'] : '';
        $data['activity_id'] = isset($data['activity_id']) && $data['activity_id'] ? $data['activity_id'] : 0;
        $data['gm_id'] = $this->GMID;
        
        DB::beginTransaction();
        try {
            $article = Article::create($data);
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
     * swl 改 2020-4-1
     * @param ArticleRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ArticleRequest $request)
    {
        $id = intval($request->id);
        if ($id <= 0) {
            return $this->resFailed(414);
        }

        $data = $request->only('title', 'listorder', 'article_url', 'content', 'is_show','subtitle','title_is_show','activity_id','type');

        DB::beginTransaction();
        try {
            $data['listorder'] = isset($data['listorder']) && $data['listorder'] ? $data['listorder'] : 0;
            $data['content'] = isset($data['content']) && $data['content'] ? $data['content'] : '';
            $data['activity_id'] = isset($data['activity_id']) && $data['activity_id'] ? $data['activity_id'] : 0;
            $data['verify_status'] = 0;//修改文章后就变成待审核状态
            $article = Article::find($id);
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

        try {
            Article::destroy($id);
        } catch (\Exception $e) {
            return $this->resFailed(701, $e->getMessage());
        }

        return $this->resSuccess();
    }

}