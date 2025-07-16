<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insertOrIgnore([
            [
                'name' => 'national-admin',
                'guard_name' => 'api',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => 'state-dmin',
                'guard_name' => 'api',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => 'member',
                'guard_name' => 'api',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => 'support-staff',
                'guard_name' => 'api',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => "guest",
                'guard_name' => 'api',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ]
        ]);
    }
}
