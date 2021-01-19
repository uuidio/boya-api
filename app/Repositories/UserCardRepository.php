<?php

/**
 * @Author: nlx
 * @Date:   2020-03-26 16:33:11
 * @Last Modified by:   nlx
 * @Last Modified time: 2020-04-11 19:31:07
 */
namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\YiTianUserCard;
use ShopEM\Models\GmPlatform;
use Illuminate\Support\Facades\Auth;

class UserCardRepository
{

    /**
     * 定义搜索过滤字段
     *
     * @var array
     */
    protected $filterAbles = [
        'gm_id' => ['field' => 'gm_id', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields($is_show='')
    {
        return [
            ['dataIndex' => 'card_code', 'title' => '卡代码'],
            ['dataIndex' => 'card_name', 'title' => '卡名称'],
            ['dataIndex' => 'level',    'title' => '卡等级'],
            ['dataIndex' => 'card_img', 'title' => '卡照片'],
            ['dataIndex' => 'gm_name', 'title' => '所属项目','hide'=>isshow_models($is_show,['group'])],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author hfh_wind
     * @return array
     *
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }
    /**
     * 列表搜索
     *
     * @Author moocde <mo@mocode.cn>
     * @param Request $request
     * @return mixed
     */
    public function listItems($request=[])
    {
        $page_size = empty($request['page_size']) ? config('app.page_size') : $request['page_size'];

        $model = new YiTianUserCard();
        $model = filterModel($model, $this->filterAbles, $request);
        $lists = $model->orderBy('level', 'asc')->paginate($page_size);

        return $lists;
    }


    /**
     * 小程序端--列表搜索
     *
     * @Author swl 2020-4-2
     * @param Request $request
     * @return mixed
     */
    public function shopListItems($request=[])
    {
        $page_size = empty($request['page_size']) ? config('app.page_size') : $request['page_size'];

        $lists = YiTianUserCard::select('gm_id');
        $selfGmid = GmPlatform::gmSelf();
        if (isset($request['is_self']) && $request['is_self'] > 0) 
        {
            $lists = $lists->where('gm_id','=',$selfGmid);
        }
        else
        {
            $lists = $lists->where('gm_id','!=',$selfGmid);
        }
        $lists = $lists->groupBy('gm_id')->paginate($page_size);
        foreach ($lists as $key => &$value) {
            $card_list = YiTianUserCard::where('gm_id',$value['gm_id'])->orderBy('level','asc')->get();
            $value['card_list'] = $card_list;
        }
        return $lists;

    }

}