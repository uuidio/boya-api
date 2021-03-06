<?php
/**
 * @Filename        LivesRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\Autocue;

class AutocueRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'live_id' => ['field' => 'live_id', 'operator' => '='],
        'cid' => ['field' => 'cid', 'operator' => '='],
    ];

    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        //根据前端要求修改返回的数据格式
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
            ['key' => 'title', 'dataIndex' => 'title', 'title' => '标题'],
            ['key' => 'antistop_one', 'dataIndex' => 'antistop_one', 'title' => '标签1'],
            ['key' => 'antistop_two', 'dataIndex' => 'antistop_two', 'title' => '标签2'],
            ['key' => 'antistop_three', 'dataIndex' => 'antistop_three', 'title' => '标签3'],
            ['key' => 'sort', 'dataIndex' => 'sort', 'title' => '排序'],
        ];
    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields()
    {
        return listFieldToShow($this->listFields());
    }

    /**
     * 列表
     *
     * @Author hfh
     * @param $request
     * @return mixed
     */
    public function listItems($request)
    {
        $request['per_page'] = isset($request['per_page']) && $request['per_page'] ? $request['per_page'] : config('app.per_page');
//        $LiveUsersModel = new Autocue();
//        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

        # $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get()->paginate($request['per_page']);
//        $lists = $LiveUsersModel->orderBy('id', 'desc')->paginate($request['per_page']);


        $lists = Autocue::where('uid', $request['uid'])
            ->when(!empty($request['cid']), function (Builder $builder) use ($request) {
                $builder->where('cid', $request['cid']);
            })
            ->where('uid', $request['uid'])
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request['per_page']);

        return $lists;
    }


    /**
     * 搜索店铺
     *
     * @Author hfh
     * @param $request
     * @return mixed
     */
    public function search($request)
    {
        $LiveUsersModel = new Autocue();
        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

        $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get();

        return $lists;
    }
}