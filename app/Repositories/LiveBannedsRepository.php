<?php
/**
 * @Filename BrandRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\Cache;
use ShopEM\Models\LiveBanned;


class LiveBannedsRepository
{
    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
        ];
    }
    /*
         * 定义搜索过滤字段
         */
    protected $filterables = [
        'live_id'   => ['field' => 'live_id', 'operator' => '='],
    ];
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
     * 获取列表数据
     *
     * @Author linzhe
     * @param int $shop_id
     * @return mixed
     */
    public function list($data)
    {
        $bannedModel = new LiveBanned();
        $bannedModel = filterModel($bannedModel, $this->filterables, $data);
        #$bannedModel->select('live_banneds.*','wx_userinfos.nickname')
        #    ->leftJoin('wx_userinfos', 'wx_userinfos.user_id', '=', 'live_banneds.user_id');
        $bannedModel = $bannedModel->where('live_id', $data['live_id']);

        #$bannedModel->leftJoin('wx_userinfos', 'wx_userinfos.user_id', '=', 'live_banneds.user_id')->select('live_banneds.*','wx_userinfos.sex');
        #dd($data);
        return $bannedModel->orderBy('id', 'desc')->paginate(config('app.per_page'));
    }
}