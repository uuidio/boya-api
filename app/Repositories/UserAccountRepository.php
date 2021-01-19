<?php
/**
 * @Filename        UserAccountRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          moocde <mo@mocode.cn>
 */

namespace ShopEM\Repositories;

use Illuminate\Support\Facades\DB;
use ShopEM\Models\UserAccount;
use ShopEM\Models\UserRelYitianInfo;

class UserAccountRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'account' => ['field' => 'user_accounts.login_account', 'operator' => '='],
        'mobile' => ['field' => 'user_accounts.mobile', 'operator' => '='],
        'created_start'  => ['field' => 'user_accounts.created_at', 'operator' => '>='],
        'created_end'  => ['field' => 'user_accounts.created_at', 'operator' => '<='],
        'partner_id'  => ['field' => 'user_accounts.partner_id', 'operator' => '='],
        'partner_role'  => ['field' => 'user_accounts.partner_role', 'operator' => '='],
        'pid'  => ['field' => 'user_accounts.pid', 'operator' => '='],
//        'sex'  => ['field' => 'user_accounts.sex', 'operator' => '='],
//        'default_gm_name'  => ['field' => 'user_accounts.default_gm_name', 'operator' => '='],
//        'user_register_platform'  => ['field' => 'user_accounts.user_register_platform', 'operator' => '='],
    ];

    /*
     * 字段信息
     */
//     protected $listFields = [
//         ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
// //        ['key' => 'login_account', 'dataIndex' => 'login_account', 'title' => '登录账号'],
//         ['key' => 'mobile', 'dataIndex' => 'mobile', 'title' => '手机'],
//         ['key' => 'mobile', 'dataIndex' => 'real_name', 'title' => '真实姓名'],
//         ['key' => 'mobile', 'dataIndex' => 'birthday', 'title' => '生日日期'],
//         ['key' => 'mobile', 'dataIndex' => 'sex', 'title' => '性别'],
//         ['key' => 'nick_name', 'dataIndex' => 'nick_name', 'title' => '昵称'],
//         ['key' => 'new_yitian_user_text', 'dataIndex' => 'new_yitian_user_text', 'title' => '益田新会员'],
//         ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '创建时间'],
//     ];


        /**
     * 查询字段
     *
     * @Author hfh_wind
     * @return array
     */
    public function listFields($is_show='')
    {
        return [
        ['dataIndex' => 'login_account', 'title' => '账号'],
        ['dataIndex' => 'real_name', 'title' => '真实姓名'],
        ['dataIndex' => 'email', 'title' => '邮箱'],
        ['dataIndex' => 'birthday', 'title' => '生日'],
        ['dataIndex' => 'mobile', 'title' => '手机号'],
        ['dataIndex' => 'nick_name', 'title' => '昵称'],
        ['dataIndex' => 'sex', 'title' => '性别'],
        ['dataIndex' => 'created_at', 'title' => '创建时间'],
        ['dataIndex' => 'default_gm_name', 'title' => '默认项目'],
        ['dataIndex' => 'user_register_platform', 'title' => '所在项目','hide'=>isshow_models($is_show,['group'])],
            // ['key' => 'new_yitian_user_text', 'dataIndex' => 'new_yitian_user_text', 'title' => '是否新会员'],
        ];

    }

    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listShowFields($is_show='')
    {
        return listFieldToShow($this->listFields($is_show));
    }

    /**
     * 获取列表数据
     *
     * @Author moocde <mo@mocode.cn>
     * @return mixed
     */
    public function listItems($request)
    {
        $model = new UserAccount();
        $model = filterModel($model, $this->filterables, $request);

        $model = $model->select(
            'user_accounts.*','wx_userinfos.nickname as nick_name','wx_userinfos.sex',
            'wx_userinfos.real_name','wx_userinfos.birthday','wx_userinfos.headimgurl as head_img_url',
            DB::raw("CASE em_wx_userinfos.sex WHEN 1 THEN '男' WHEN 2 THEN '女' WHEN 0 THEN '女' END  AS sex"))
            ->leftJoin('wx_userinfos','user_accounts.openid','=','wx_userinfos.openid');

        if (isset($request['gm_id']))
        {
            $user_ids = UserRelYitianInfo::where(['gm_id'=>$request['gm_id']])->pluck('user_id');
            $model = $model->whereIn('user_accounts.id',$user_ids);
        }
        if(isset($request['sex'])){//筛选性别
            if($request['sex']==0){
                $model = $model->where(DB::raw('(em_wx_userinfos.sex='.$request['sex'].' or em_wx_userinfos.sex=2)'));//性别为女字段值可能为2
            }else{
                $model = $model->where('wx_userinfos.sex',$request['sex']);
            }

        }
        if(isset($request['default_gm_name'])){//筛选默认项目
            $user_ids = UserRelYitianInfo::where(['gm_id'=>$request['default_gm_name'],'default'=>'1'])->pluck('user_id');
            $model = $model->whereIn('user_accounts.id',$user_ids);
        }
        if(isset($request['user_register_platform'])){//筛选所在项目
            $platformArr=explode(',',$request['user_register_platform']);
            $sql ='(select user_id ,group_concat(gm_id) as user_register_platform from  em_user_rel_yitian_infos GROUP BY  user_id) as User_platform';
            $where='';
            $count=count($platformArr);
            foreach($platformArr as $key=>$value){
                if($key<$count-1){
                    $where.=" FIND_IN_SET(?,user_register_platform) and ";
                }else{
                    $where.=" FIND_IN_SET(?,user_register_platform)";
                }

            }

            $user_ids = DB::table(DB::raw($sql))->whereRaw($where,$platformArr)->pluck('user_id');

            $model = $model->whereIn('user_accounts.id',$user_ids);
        }

        $lists = $model->orderBy('user_accounts.created_at', 'desc')->paginate($request['per_page']);
        return $lists;
    }

    public function extensionList($request, $down = '')
    {
        $model = new UserAccount();
        if (isset($request['partner_mobile'])) {
            $user_id = UserAccount::where('mobile', $request['partner_mobile'])->value('id');
            if ($user_id) {
                $request['partner_id'] = $user_id;
                unset($request['partner_mobile']);
            } else {
                return [];
            }
        }


        $model = filterModel($model, $this->filterables, $request);


        if(isset($request['e_card_status']) || isset($request['exchange_error'])){
            $model=$model->orderBy('freezes_times', 'desc');
        }

        if ($down) {

            $lists = $model->orderBy('id', 'desc')->get();
        } else {
            $lists = $model->orderBy('id', 'desc')->paginate($request['per_page']);
        }

        return $lists;
    }


    /**
     * 获取全部会员
     *
     * @Author swl
     * @return mixed
     */
    public function allListItems($request,$page_count=0)
    {
        $model = new UserAccount();
        $model = filterModel($model, $this->filterables, $request);
        $page_count = $page_count == 0 ? config('app.per_page') : $page_count;

        $lists = $model->select(
            'user_accounts.*','wx_userinfos.nickname as nick_name','wx_userinfos.sex',
            'wx_userinfos.real_name','wx_userinfos.birthday',
            DB::raw("CASE em_wx_userinfos.sex WHEN 1 THEN '男' WHEN 2 THEN '女' WHEN 0 THEN '女' END  AS sex"))
            ->leftJoin('wx_userinfos','user_accounts.openid','=','wx_userinfos.openid');

        $lists = $model->orderBy('user_accounts.created_at', 'desc') ->paginate($request['per_page']);
        return $lists;
    }

    /**
     * 会员信息
     *
     * @Author moocde <mo@mocode.cn>
     * @param $user_id
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|null|object
     */
    public function userinfo($user_id)
    {
        return UserAccount::select('id', 'login_account', 'email', 'mobile')
            ->where('id', $user_id)
            ->first();
    }

    public function  userWalletInfo($user_id)
    {
        $user = DB::table('user_accounts')->leftJoin('user_wallets' ,'user_wallets.user_id' , '=' , 'user_accounts.id')
                ->select( 'user_accounts.mobile','user_wallets.*' )
                ->where('user_accounts.id', $user_id)
                ->first();
        return $user;
    }

    /**
     * 顾客
     * @Author RJie
     * @return array
     */
    public function customerListFields()
    {
        return [
            ['dataIndex' => 'id', 'title' => '会员ID'],
            ['dataIndex' => 'mobile', 'title' => '手机号'],
            ['dataIndex' => 'nickname', 'title' => '用户昵称'],
            ['dataIndex' => 'headimgurl', 'title' => '用户头像'],
            ['dataIndex' => 'surplus', 'title' => '剩余解绑天数'],
            ['dataIndex' => 'hold_text', 'title' => '状态'],
            ['dataIndex' => 'countAccount', 'title' => '成交额'],
            ['dataIndex' => 'countTrade', 'title' => '订单数'],
            ['dataIndex' => 'created_at', 'title' => '录制时间'],
        ];

    }


    /**
     * 顾客
     * @Author RJie
     * @return array
     */
    public function customerListShowFields()
    {
        return listFieldToShow($this->customerListFields());
    }


    protected $woaListFields = [
        ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
        ['key' => 'login_account', 'dataIndex' => 'login_account', 'title' => '公众号名称'],
        ['key' => 'woa_code', 'dataIndex' => 'woa_code', 'title' => '公众号标识'],
        ['key' => 'child_num', 'dataIndex' => 'child_num', 'title' => '公众号拉新数'],
        ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '创建时间'],
    ];


    /**
     * 后台表格列表显示字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function woaListShowFields()
    {
        return listFieldToShow($this->woaListFields);
    }


    protected $extensionFields = [
        ['key' => 'id', 'dataIndex' => 'id', 'title' => 'ID'],
        ['key' => 'mobile', 'dataIndex' => 'mobile', 'title' => '手机'],
        ['key' => 'nick_name', 'dataIndex' => 'nick_name', 'title' => '昵称'],
        ['key' => 'child_num', 'dataIndex' => 'child_num', 'title' => '邀请好友数'],
        ['key' => 'related', 'dataIndex' => 'related', 'title' => '客户数'],
        ['key' => 'role_type', 'dataIndex' => 'role_type', 'title' => '角色'],
        ['key' => 'partner', 'dataIndex' => 'partner', 'title' => '上级手机'],
        ['key' => 'created_at', 'dataIndex' => 'created_at', 'title' => '创建时间'],
    ];

    public function extensionShowFields()
    {
        return listFieldToShow($this->extensionFields);
    }
}
