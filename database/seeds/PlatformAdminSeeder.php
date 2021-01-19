<?php

use Illuminate\Database\Seeder;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'username' => 'admin',
            'password' => bcrypt('admin@123'),
        ];
        \ShopEM\Models\PlatformAdmin::create($data);
    }
}
