<?php

use Illuminate\Database\Seeder;

class GroupManageUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'role_id'  => '1',
            'is_root'  => '1',
            'username' => 'gm@admin',
            'password' => bcrypt('gmadmin@123'),
        ];
        \ShopEM\Models\GroupManageUser::create($data);

        $data = [
            'role_id' => '1',
            'is_root' => '1',
            'password' => bcrypt('szadmin@123'),
        ];
        \ShopEM\Models\AdminUsers::create($data);

        $admin = \ShopEM\Models\AdminUsers::where('role_id',1)->where('is_root',1)->first();
        $gmdata = array(
            'gm_id' => 1,
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'platform_name' => '深圳益田假日广场'
        );
        \ShopEM\Models\GmPlatform::create($gmdata);
    }
}
