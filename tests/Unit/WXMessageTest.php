<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WXMessageTest extends TestCase
{
    public function testBasicTest()
    {
        $this->post('/shop/v1/wechatmini/push-point-change-message',['mobile'=>18902391764,'change'=>10,'point'=>10,'time'=>'2020-05-06 17:44:20','reason'=>'测试'])->assertStatus(200);
    }
}
