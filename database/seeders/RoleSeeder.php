<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'name' => 'National Admin',
                'guard_name' => 'national-admin',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => 'State Admin',
                'guard_name' => 'state-admin',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => 'Member',
                'guard_name' => 'member',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => 'Support Staff',
                'guard_name' => 'support-staff',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ],
            [
                'name' => "Guest",
                'guard_name' => 'guest',
                'created_at' => Date::now(),
                'updated_at' => Date::now(),
            ]
        ]);
    }
}
