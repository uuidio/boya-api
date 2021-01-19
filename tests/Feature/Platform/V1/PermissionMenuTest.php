<?php

namespace Tests\Feature\Platform\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionMenuTest extends TestCase
{
    public function testCreatePermissionMenu()
    {
        $this->withHeaders($this->platform_with_headers)
            ->postJson(env('APP_URL') . 'platform/v1/permission/createMenu',
                [
                    'parent_id'           => 0,
                    'route_path'          => 'platform/v1/permission/createMenuTest',
                    'route_name'          => 'permission.create.menu.test',
                    'frontend_route_path' => 'permission/createMenu.test',
                    'frontend_route_name' => 'permission.create.menu.test',
                    'title'               => '新增后台菜单.test',
                    'icon'                => null,
                    'hide'                => 0,
                    'listorder'           => 0,
                    'is_dev'              => 0,
                    'remark'              => 'test remark',
                ])
            ->assertJsonStructure(['code', 'message', 'result']);
    }

    public function testUpdatePermissionMenu()
    {
        $response =$this->withHeaders($this->platform_with_headers)
            ->postJson(env('APP_URL') . 'platform/v1/permission/updateMenu',
                [
                    'id'                  => 1,
                    'parent_id'           => 0,
                    'route_path'          => 'platform/v1/permission/createMenuTestUpdate',
                    'route_name'          => 'permission.create.menu.test',
                    'frontend_route_path' => 'permission/createMenu.test',
                    'frontend_route_name' => 'permission.create.menu.test',
                    'title'               => '新增后台菜单.test',
                    'icon'                => null,
                    'hide'                => 0,
                    'listorder'           => 0,
                    'is_dev'              => 0,
                    'remark'              => 'test remark',
                ])
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertTrue(in_array($response->original['code'], [0, 702]));
    }

    public function testDeletePermissionMenu()
    {
        $response = $this->withHeaders($this->platform_with_headers)
            ->getJson(env('APP_URL') . 'platform/v1/permission/deleteMenu?id=3')
            ->assertJsonStructure(['code', 'message', 'result']);

        $this->assertTrue(in_array($response->original['code'], [0, 700]));
    }
}
