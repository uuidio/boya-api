<?php

/**
 * @Author: nlx
 * @Date:   2020-03-07 13:37:47
 * @Last Modified by:   nlx
 */
return [

	0 => [
            'page' => 'shop',
            'group' => 'point',
            'value' => json_encode(
                array(
                    'open_point_deduction'	=>['name'=>'开启积分抵扣','value'=>0,'type'=>'switch'],
                    'open_point_gain'		=> ['name'=>'开启确认收货送积分','value'=>0,'type'=>'switch'],
                    'point_deduction_max'   =>['name'=>'积分抵扣上限','value'=>0,'type'=>'number'],
                    'point_deduction_rate'  =>['name'=>'积分抵扣比率','value'=>0,'type'=>'number'],
                    // 'open_register_point'	=>['name'=>'开启注册赠送积分','value'=>0,'type'=>'switch'],
                    // 'register_point_number'	=>['name'=>'注册赠送积分','value'=>0,'type'=>'number'],
                )
            ),
        ],
	1 => [
            'page' => 'shop',
            'group' => 'free_order_amount',
            'value' => json_encode(
                array(
                    'decr_rules'=>[['decr_post_fee'=>0,'order_amount'=>0,'status'=>0,'value'=>1]],
                    'free_rules' => ['free_order_amount'=>"1000",'status'=>0],
                    'new_user_rules' => ['status'=>1],
                )
            ),
        ],
	2 => [
            'page' => 'shop',
            'group' => 'trade',
            'value' => json_encode(
                array(
                    'trade_finish_spacing_time'	=>['name'=>'交易完成（自动收货）间隔时间（天）','value'=>5,'type'=>'number'],
                    'open_aftersalse_refund'	=>['name'=>'开启退货时间限制','value'=>0,'type'=>'switch'],
                    'open_aftersalse_changing'	=>['name'=>'开启换货时间限制','value'=>0,'type'=>'switch'],
                    'aftersalse_refund_day'		=>['name'=>'退货时间限定（天），为0时，则表示不允许换货','value'=>0,'type'=>'number'],
                    'aftersalse_changing_day'	=>['name'=>'换货时间限定（天），为0时，则表示不允许换货','value'=>0,'type'=>'number'],
                )
            ),
        ],
	3 => [
            'page' => 'shop',
            'group' => 'banner',
            'value' => json_encode(
                array(
                    'user_center_bottom_image'=>['name'=>'会员中心底部广告图','type'=>'image','value'=>[
                        [
                            "status"=> "finished",
                            "name"  => "43ead0f01b80d150da72df362155666e2921737b16627-LagGa0_fw658.png",
                            "size"  => "95574",
                            "percentage"    => "100",
                            "uid"   => "1575017381430",
                            "showProgress"  => "false",
                            "url"   =>"https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201911/29/c4S2SWh4I6TpUqy4JX0yrkge6949ihZzr06wBvqE.png",
                            "response"  => [
                                "code"      =>"0",
                                "message"   =>"success!",
                                "result"    => ["pic_url" => "https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201911/29/c4S2SWh4I6TpUqy4JX0yrkge6949ihZzr06wBvqE.png"]
                            ],
                        ]
                    ]],
                    // 'point_banner'=>['name'=>'积分+现金活动页横幅','type'=>'image','value'=>[
                    //     [
                    //         "status"=> "finished",
                    //         "name"  => "12345.jpg",
                    //         "size"  => "67711",
                    //         "percentage"    => "100",
                    //         "uid"   => "1575017133455",
                    //         "showProgress"  => "false",
                    //         "url"   =>"https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201911/29/P6nZaWZjOeGRwOoz7q0yl9ZC3Hurgfw2jMkVseaL.jpeg",
                    //         "response"  => [
                    //             "code"      =>"0",
                    //             "message"   =>"success!",
                    //             "result"    => ["pic_url" => "https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201911/29/P6nZaWZjOeGRwOoz7q0yl9ZC3Hurgfw2jMkVseaL.jpeg"]
                    //         ],
                    //     ]
                    // ]],
                    'groups_banner'=>['name'=>'拼团活动页横幅','type'=>'image','value'=>[
                        [
                            "status"=> "finished",
                            "name"  => "3a690d45b89c9656993111bad1f96e666b51b3d738b5b-URqReW_fw658.jpeg",
                            "size"  => "39317",
                            "percentage"    => "100",
                            "uid"   => "1575017331950",
                            "showProgress"  => "false",
                            "url"   =>"https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201911/29/OLQ7ZGaPeEF5YLzIAzpaGXLejrYUmld4svhne7Lp.jpeg",
                            "response"  => [
                                "code"      =>"0",
                                "message"   =>"success!",
                                "result"    => ["pic_url" => "https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201911/29/OLQ7ZGaPeEF5YLzIAzpaGXLejrYUmld4svhne7Lp.jpeg"]
                            ],
                        ]
                    ]],
                )
            ),
        ],
	4 => [
            'page' => 'shop',
            'group' => 'base',
            'value' => json_encode(
                array(
                    'shop_cs_mobile'=>['name' => '客服电话', 'value' => '0000000', 'type' => 'number'],
                    'shop_cs_weixin'=>['name' => '客服微信', 'type' => 'image', 'value' => []],
                )
            ),
        ],
	5 => [
            'page' => 'index',
            'group' => 'pop',
            'value' => json_encode(
                array(
                    'pop_switch'=>['name'=>"弹窗图片开关",'type'=>"switch",'value'=>0],
                    'pop_time'=>['name'=>"弹窗图片显示时长（秒）",'type'=>"number",'value'=>0],
                    'pop_url'=>['name'=>'弹窗图片','type'=>"url",'value'=>[
                    	'id'=>null,
                    	'shop_type'=>null,
                    	'image_url'=>"https://yt-ego-oss.oss-cn-shenzhen.aliyuncs.com/images/default/201912/18/4TnXA5u95XH80PsBOw7c17f9vIR3bD8vJshXy3IG.jpeg",
                    	'show_type'=>'customActivity',
                    ]],
                )
            ),
        ],
    // 6 => [
    //         'page' => 'wechat',
    //         'group' => 'live',
    //         'value' => json_encode(
    //             array(
    //                 'room_status'=>['name'=>"直播显示开关",'type'=>"switch",'value'=>0],
    //                 'roomid'=>['name'=>"房间号ID",'type'=>"number",'value'=>0],
    //                 'pic_url'=>['name'=>'直播展示图','type'=>"url",'value'=>[]],
    //             )
    //         ),
    //     ],

];