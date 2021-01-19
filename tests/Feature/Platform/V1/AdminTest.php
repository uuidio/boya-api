<?php

namespace Tests\Feature\Platform\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminTest extends TestCase
{
    public function testCreateAdmin()
    {
        $response = $this->withHeaders($this->platform_with_headers)
            ->postJson(env('APP_URL') . 'platform/v1/admin/create',
                [
                    'role_id'  => '12',
                    'username' => 'admin_test_' . rand(0, 100),
                    'password' => '123456',
                    'status'   => 1,
                    'is_root'  => 0,
                ])
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertEquals($response->original['code'], 0);
    }

    public function testUpdateAdmin()
    {
        $response = $this->withHeaders($this->platform_with_headers)
            ->postJson(env('APP_URL') . 'platform/v1/admin/update',
                [
                    'id'       => '100003',
                    'role_id'  => '12',
                    'username' => 'admin_test_8',
                    'password' => '1234562',
                    'status'   => 0,
                    'is_root'  => 1,
                ])
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertEquals($response->original['code'], 0);
    }

    public function testDeleteAdmin()
    {
        $response =$this->withHeaders($this->platform_with_headers)
            ->getJson(env('APP_URL') . 'platform/v1/admin/delete?id=100004')
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertEquals($response->original['code'], 0);
    }
}
