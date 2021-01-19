<?php
/**
 * @Filename ArticleController.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Http\Controllers\Seller\V1;

use ShopEM\Http\Controllers\Seller\BaseController;
use ShopEM\Repositories\ArticleRepository;
use Illuminate\Http\Request;

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
    public function lists()
    {
        $articleItems = $this->articleRepository->articleItems();

        if (empty($articleItems)) {
            return $this->resFailed(700, errorMsg(700));
        }

        return $this->resSuccess([
            'lists' => $articleItems,
            'field' => $this->articleRepository->aritcleListField()
        ]);
    }

    /**
     * [获取规则]
     * @Author swl
     * @param string $type [0:积分 1：分销]
     * 
     */
    public function getRule(Request $request){
        $type = $request->type??0;
        $data = [
            'is_show'=>1,
            'type'=>$type
       ];
       if ($request->has('gm_id')) {
            $data['gm_id'] = $request->gm_id;
       }
       $ruleModel = new \ShopEM\Models\PlatformRule;
       $lists = $ruleModel->where($data)->orderBy('listorder','asc')->first();
       return $this->resSuccess($lists);
    }
}