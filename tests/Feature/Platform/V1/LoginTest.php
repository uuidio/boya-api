<?php

namespace Tests\Feature\Platform\V1;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    protected $access_token;

    protected function setUp(): void
    {
        parent::setUp();

        $response = $this->postJson(env('APP_URL') . 'platform/v1/passport/login',
            ['username' => 'admin', 'password' => 'admin@123']);

        $this->access_token = $response->original['result']['access_token'];
    }

    public function testLogin()
    {
        $response = $this->postJson(env('APP_URL') . 'platform/v1/passport/login',
            ['username' => 'admin', 'password' => 'admin@123']);

        $response->assertStatus(200);
        $response->assertSuccessful()
            ->assertJsonStructure(['code', 'message', 'result' => ['token_type', 'expires_in', 'access_token']])
            ->assertJson(['code' => 0]);
    }

    public function testLogout()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->access_token])
            ->getJson(env('APP_URL') . 'platform/v1/passport/logout');

        $response->assertStatus(200);
        $response->assertSuccessful()
            ->assertJsonStructure(['code', 'message', 'result'])
            ->assertJson(['code' => 0]);
    }

}
