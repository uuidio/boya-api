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
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use ShopEM\Models\GmPlatform;
use ShopEM\Models\LiveUsers;

class LiveUsersRepository
{

    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'id' => ['field' => 'id', 'operator' => '='],
        'login_account' => ['field' => 'login_account', 'operator' => '='],
        'shop_id' => ['field' => 'shop_id', 'operator' => '='],
        'platform_id' => ['field' => 'platform_id', 'operator' => '='],
        'mobile' => ['field' => 'mobile', 'operator' => '='],
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
            ['key' => 'mobile', 'dataIndex' => 'mobile', 'title' => '手机号码'],
            ['key' => 'shop_id', 'dataIndex' => 'shop_id', 'title' => '门店'],
            ['key' => 'platform_id', 'dataIndex' => 'platform_id', 'title' => '品牌'],
            ['key' => 'company', 'dataIndex' => 'company', 'title' => '公司名称'],
            ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '注册时间'],
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
    public function list($request)
    {
        $LiveUsersModel = new LiveUsers();
        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

        $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get();

        return $lists;
    }

    public function platformGetUserList($platform_id)
    {
        //主播名称
        $searchUsername = request()->input('username');
        //主播手机号
        $searchMobile = request()->input('mobile');
        //门店
        $searchShopId = request()->input('shop_id');

        $per_page = request()->input('per_page', config('app.per_page'));

        $lists = LiveUsers::where('platform_id', $platform_id)
            ->when($searchUsername, function (Builder $builder) use ($searchUsername) {
                $builder->where('username', 'like', '%' . $searchUsername . '%');
            })
            ->when($searchMobile, function (Builder $builder) use ($searchMobile) {
                $builder->where('mobile', $searchMobile);
            })
            ->when($searchShopId, function (Builder $builder) use ($searchShopId) {
                $builder->where('shop_id', $searchShopId);
            })
            ->orderBy('id', 'desc')
            ->select([
                'id', 'mobile', 'username', 'live_id', 'created_at', 'company', 'platform_id', 'shop_id', 'account_end_time'
            ])
            ->paginate($per_page);

        if ($lists->isNotEmpty()) {
            foreach ($lists->items() as &$item) {
                $item->account_end_time = !empty($item->account_end_time) ? date('Y-m-d', $item->account_end_time) : '-';
                $item->platform_name = DB::table('gm_platforms')->where('gm_id', $item->platform_id)->value('platform_name');
                $item->shop_name = DB::table('shops')->where('id', $item->shop_id)->value('shop_name');
            }
        }

        return $lists;
    }

    /**
     * 列表
     *
     * @return mixed
     */
    public function getUserList()
    {
        //主播名称
        $searchUsername = request()->input('username');
        //主播手机号
        $searchMobile = request()->input('mobile');
        //是否绑定品牌
        $searchIsBindPlatform = request()->input('bind_platform'); //-1未绑定 0全部 1已绑定

        //门店
        $searchShopId = request()->input('shop_id');

        //品牌
        $searchPlatformId = request()->input('platform_id');
        $searchShopIds = [];
        if ($searchPlatformId && empty($searchShopId)) {
            $shops = DB::table('shops')->where('gm_id', $searchPlatformId)->get()->toArray();
            if($shops) $searchShopIds = array_column($shops,'id');
        }

        $per_page = request()->input('per_page', config('app.per_page'));

        $lists = (new LiveUsers())
            ->when($searchUsername, function (Builder $builder) use ($searchUsername) {
                $builder->where('username', 'like', '%' . $searchUsername . '%');
            })
            ->when($searchMobile, function (Builder $builder) use ($searchMobile) {
                $builder->where('mobile', $searchMobile);
            })
            ->when(in_array($searchIsBindPlatform, [-1, 1]), function (Builder $builder) use ($searchIsBindPlatform) {
                if ($searchIsBindPlatform == -1) $builder->where('platform_id', 0);
                if ($searchIsBindPlatform == 1) $builder->where('platform_id', '>', 0);
            })
            ->when($searchShopIds, function (Builder $builder) use ($searchShopIds) {
                $builder->whereIn('shop_id', $searchShopIds);
            })
            ->when($searchShopId, function (Builder $builder) use ($searchShopId) {
                $builder->where('shop_id', $searchShopId);
            })
            ->orderBy('id', 'desc')
            ->select([
                'id', 'mobile', 'username', 'live_id', 'created_at', 'company', 'platform_id', 'shop_id', 'account_end_time'
            ])
            ->paginate($per_page);

        if ($lists->isNotEmpty()) {
            foreach ($lists->items() as &$item) {
                $item->account_end_time = !empty($item->account_end_time) ? date('Y-m-d', $item->account_end_time) : '-';
                $item->platform_name = DB::table('gm_platforms')->where('gm_id', $item->platform_id)->value('platform_name');
                $item->shop_name = DB::table('shops')->where('id', $item->shop_id)->value('shop_name');
            }
        }

        return $lists;
    }

    /**
     * 品牌下的所有门店
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPlatformsShops()
    {
        $lists = DB::table('gm_platforms')->select(['gm_id', 'platform_name'])->get()->each(function ($item) {
            $item->shops = DB::table('shops')->where('gm_id', $item->gm_id)->select(['id', 'shop_name'])->get();
        });

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
        $LiveUsersModel = new LiveUsers();
        $LiveUsersModel = filterModel($LiveUsersModel, $this->filterables, $request);

        $lists = $LiveUsersModel->select(listFieldToSelect($this->listShowFields()))->get();

        return $lists;
    }

    /**
     * 会员信息
     *
     * @Author linzhe
     * @param $user_id
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null|object
     */
    public function userinfo($user_id)
    {
        return LiveUsers::select('id', 'login_account', 'mobile', 'live_id', 'shop_id', 'username', 'img_url')
            ->where('id', $user_id)
            ->first();
    }

}