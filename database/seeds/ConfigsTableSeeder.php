<?php

use Illuminate\Database\Seeder;

class ConfigsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'page' => 'shop',
            'group' => 'point',
            'value' => '{"point_deduction_max":{"name":"\u79ef\u5206\u62b5\u6263\u4e0a\u9650","value":10,"type":"number"},"point_deduction_rate":{"name":"\u79ef\u5206\u62b5\u6263\u6bd4\u7387","value":333.3,"type":"number"},"open_point_deduction":{"name":"\u662f\u5426\u5f00\u542f\u79ef\u5206\u62b5\u6263","value":1,"type":"switch"},"open_point_goods_deduction":{"name":"\u5141\u8bb8\u79ef\u5206\u4e13\u533a\u7684\u5546\u54c1\u4f7f\u7528\u4f18\u60e0\u5238","value":1,"type":"switch"}}',
        ];

        \ShopEM\Models\Config::create($data);
        $data = [
            'page' => 'index',
            'group' => 'pop',
            'value' => '{"pop_switch":{"name":"\u5f39\u7a97\u56fe\u7247\u5f00\u5173","value":1,"type":"switch"},"pop_time":{"name":"\u5f39\u7a97\u56fe\u7247\u663e\u793a\u65f6\u957f\uff08\u79d2\uff09","value":5,"type":"number"},"pop_image":{"name":"\u5f39\u7a97\u56fe\u7247","value":"","type":"image"},"pop_url":{"name":"\u5f39\u7a97\u56fe\u7247\u8df3\u8f6c\u5730\u5740","value":{"show_type":"goods","id":1},"type":"url"}}',
        ];

        \ShopEM\Models\Config::create($data);
    }
}
