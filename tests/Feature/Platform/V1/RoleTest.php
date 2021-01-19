<?php

namespace Tests\Feature\Platform\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    public function testCreateRole()
    {
        $response = $this->withHeaders($this->platform_with_headers)
            ->postJson(env('APP_URL') . 'platform/v1/role/create',
                [
                    'name'      => 'test role',
                    'menus'     => [1, 2],
                    'status'    => 0,
                    'listorder' => 0,
                    'remark'    => 'test remark',
                ])
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertEquals($response->original['code'], 0);
    }

    public function testUpdateRole()
    {
        $response = $this->withHeaders($this->platform_with_headers)
            ->postJson(env('APP_URL') . 'platform/v1/role/update',
                [
                    'id'        => 2,
                    'name'      => 'test role update',
                    'menus'     => [11, 21],
                    'status'    => 0,
                    'listorder' => 0,
                    'remark'    => 'test remark',
                ])
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertEquals($response->original['code'], 0);
    }

    public function testDeleteRole()
    {
        $response =$this->withHeaders($this->platform_with_headers)
            ->getJson(env('APP_URL') . 'platform/v1/role/delete?id=11')
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertEquals($response->original['code'], 0);
    }


}
