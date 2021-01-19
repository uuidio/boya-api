<?php

use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'username' => 'mocode',
            'password' => bcrypt('mocode@123'),
        ];

        \ShopEM\Models\Member::create($data);
    }
}
