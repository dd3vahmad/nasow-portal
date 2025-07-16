<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'view members',
            'manage members',
            'verify membership',
            'edit own profile',
            'log cpd',
            'view cpd',
            'manage cpd',
            'view payments',
            'manage payments',
            'submit ticket',
            'view tickets',
            'manage tickets',
            'view policies',
            'manage policies',
            'view news',
            'manage news',
            'view events',
            'manage events',
            'view reports',
            'manage reports',
        ];

        $now = Date::now();

        $insertData = array_map(fn($perm) => [
            'name' => $perm,
            'guard_name' => 'web',
            'created_at' => $now,
            'updated_at' => $now,
        ], $permissions);

        DB::table('permissions')->insertOrIgnore($insertData);
    }
}
