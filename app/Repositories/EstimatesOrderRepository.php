<?php
/**
 * @Filename        EstimatesOrderRepository.php
 *
 * @Copyright       Copyright (c) 2015~2020 <http://www.shopem.cn> All rights reserved.
 * @License         Licensed <http://www.shopem.cn/licenses/>
 * @Author          hfh
 */

namespace ShopEM\Repositories;


use ShopEM\Models\TradeEstimates;
use ShopEM\Models\TradeOrder;
use ShopEM\Models\WxUserinfo;

class EstimatesOrderRepository
{
    /*
     * 定义搜索过滤字段
     */
    protected $filterables = [
        'tid'           => ['field' => 'tid', 'operator' => '='],
        'tids'          => ['field' => 'tid', 'operator' => 'in_arr'],
        'user_id'       => ['field' => 'user_id', 'operator' => '='],
        'pid'           => ['field' => 'pid', 'operator' => '='],
        'shop_id'       => ['field' => 'shop_id', 'operator' => '='],
        'status'        => ['field' => 'status', 'operator' => '='],
        'iord'          => ['field' => 'iord', 'operator' => '='],
        'created_start' => ['field' => 'created_at', 'operator' => '>='],
        'created_end'   => ['field' => 'created_at', 'operator' => '<='],
    ];


    /**
     * 查询字段
     *
     * @Author moocde <mo@mocode.cn>
     * @return array
     */
    public function listFields()
    {
        return [
            ['dataIndex' => 'tid', 'title' => '订单号'],
            ['dataIndex' => 'status_text', 'title' => '订单状态', 'width' => 80],
            ['dataIndex' => 'activity_sign_text', 'title' => '活动类型'],
            ['dataIndex' => 'group_status_text', 'title' => '拼团状态'],
            ['dataIndex' => 'cancel_text', 'title' => '取消状态'],
            ['dataIndex' => 'user_mobile', 'title' => '下单手机号'],
            ['dataIndex' => 'amount', 'title' => '实付金额'],
            ['dataIndex' => 'total_fee', 'title' => '商品总金额'],
            ['dataIndex' => 'pick_type_name', 'title' => '提货方式'],
            ['dataIndex' => 'receiver_name', 'title' => '收货人姓名'],
            ['dataIndex' => 'receiver_tel', 'title' => '收货人电话'],
            ['dataIndex' => 'receiver_addr', 'title' => '收货人地址'],
            ['dataIndex' => 'pay_time', 'title' => '付款时间'],
            ['dataIndex' => 'created_at', 'title' => '订单创建时间'],
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
     * 订单查询
     *
     * @Author moocde <mo@mocode.cn>
     * @param $request
     * @return mixed
     */
    public function search($request)
    {

        $request['page']=$request['page']??1;

        if (isset($request['mobile'])) {
            $user = \ShopEM\Models\UserAccount::where('mobile', $request['mobile'])->first();
            if (!$user) {
                return [];
            }
            $request['user_id'] = $user['id'];
        }


        if (isset($request['nickname'])) {
            $user_info = WxUserinfo::where(['nickname' => $request['nickname'], 'user_type' => 1])->first();
            if (!empty($user_info)) {
                $request['user_id'] = $user_info['user_id'];
                unset($request['nickname']);
            } else {
                $lists['data'] = [];
                return $lists;
            }
        }


        $model = new TradeEstimates();
        $model = filterModel($model, $this->filterables, $request);

        //售后状态的
        if (isset($request['failure'])) {
            $model->where('status','<>',0);
        }

        $goods_amount =0;//分佣金额
        $total = 0;//销售金额
        $reward_value = 0;//销售金额
        $lists = $model->orderBy('id','desc')->get();
//dd($lists['first_page_url']);
        $listsData=[];
        //返回使用过积分抵扣的实际金额
        if (count($lists)>0) {

            $lists = $lists->toArray();

            $listsData = [];
            $count_oid=[];
            foreach ($lists as $key => $value) {
                $listsData[$value['tid']]['tid'] = $value['tid'];
                $listsData[$value['tid']]['created_at'] = $value['created_at'];
                $listsData[$value['tid']]['wx_nikename'] = $value['wx_info']['nickname']??'';
                $listsData[$value['tid']][$key]['shop_id'] = $value['shop_id'];
                $listsData[$value['tid']][$key]['goods_id'] = $value['goods_id'];
                $listsData[$value['tid']][$key]['user_id'] = $value['user_id'];
                $listsData[$value['tid']][$key]['pid'] = $value['pid'];
                $listsData[$value['tid']][$key]['tid'] = $value['tid'];
                $listsData[$value['tid']][$key]['oid'] = $value['oid'];
//                $listsData[$value['tid']][$key]['reward_value'] = $value['reward_value'];
                $listsData[$value['tid']][$key]['type'] = $value['type'];
                $listsData[$value['tid']][$key]['iord'] = $value['iord'];
                $listsData[$value['tid']][$key]['status'] = $value['status'];
                $listsData[$value['tid']][$key]['created_at'] = $value['created_at'];
                $orderInfo = TradeOrder::where('oid', $value['oid'])->first();
                $listsData[$value['tid']][$key]['goods_image'] = $orderInfo['goods_image'];
                $listsData[$value['tid']][$key]['trade_status'] = $orderInfo['status'];
                $listsData[$value['tid']][$key]['goods_price'] = $orderInfo['goods_price'];
                $listsData[$value['tid']][$key]['goods_name'] = $orderInfo['goods_name'];
                $listsData[$value['tid']][$key]['quantity'] = $orderInfo['quantity'];

                if(!in_array($value['oid'],$count_oid)){
                    $count_oid[$key]=$value['oid'];
                    $total +=$orderInfo['amount'];
                }

                if ($value['type'] == '1') {
                    if ($value['status'] != 0) {
                        $reward_value=$listsData[$value['tid']][$key]['shop_estimates_value'] = 0;
                    } else {
                        $reward_value=$listsData[$value['tid']][$key]['shop_estimates_value'] = $value['reward_value'];
                    }

                } else {
                    if ($value['status'] != 0) {

                        $reward_value=$listsData[$value['tid']][$key]['pt_estimates_value'] = 0;
                    } else {
                        $reward_value=$listsData[$value['tid']][$key]['pt_estimates_value'] = $value['reward_value'];
                    }
                }

                $goods_amount +=$reward_value;
            }

            $listsData = array_values($listsData);


            $listsData = $this->page_array($request['per_page'], $request['page'], $listsData, 0);
        }

        $listsData['goods_amount']=$goods_amount;
        $listsData['goods_total']=$total;
        return $listsData;
    }


    /**
     * 数组分页函数  核心函数  array_slice
     * 用此函数之前要先将数据库里面的所有数据按一定的顺序查询出来存入数组中
     * $count   每页多少条数据
     * $page   当前第几页
     * $array   查询出来的所有数组
     * order 0 - 不变     1- 反序
     */

    public function page_array($count, $page, $array, $order)
    {
        global $countpage; #定全局变量
        $page = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start = ($page - 1) * $count; #计算每次分页的开始位置
        if ($order == 1) {
            $array = array_reverse($array);
        }
        $totals = count($array);
        $countpage = ceil($totals / $count); #计算总页面数
        $pagedata = array();
        $pagedata = array_slice($array, $start, $count);

        $retrun = [
            "current_page"   => $page,
            "data"           => $pagedata,
//            "first_page_url" => "http://aofei.dev.mx/shop/v1/trade/estimatesOrderLists?page=1",
            "from"           => 1,
            "last_page"      => $countpage,
//            "last_page_url"  => "http://aofei.dev.mx/shop/v1/trade/estimatesOrderLists?page=1",
            "next_page_url"  => null,
//            "path"           => "http://aofei.dev.mx/shop/v1/trade/estimatesOrderLists",
            "per_page"       => $count,
            "prev_page_url"  => null,
            "to"             => 5,
            "total"          =>$totals
        ];
        return $retrun;  #返回查询数据
    }
}