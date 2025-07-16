<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = DB::table('roles')->pluck('id', 'name');

        $permissions = DB::table('permissions')->pluck('id', 'name');

        $rolePermissionsMap = [
            'National Admin' => $permissions->values()->all(),

            'State Admin' => [
                'view members',
                'manage members',
                'verify membership',
                'view payments',
                'manage payments',
                'view cpd',
                'manage cpd',
                'view tickets',
                'manage tickets',
                'view policies',
                'manage policies',
                'view news',
                'manage news',
                'view events',
                'manage events',
                'view reports',
            ],

            'Member' => [
                'edit own profile',
                'log cpd',
                'view cpd',
                'submit ticket',
                'view policies',
                'view news',
                'view events',
            ],

            'Support Staff' => [
                'view tickets',
                'manage tickets',
                'manage policies',
            ],

            'Guest' => [
                'view policies',
                'view news',
                'view events',
            ],
        ];

        $now = now();
        $pivotInsert = [];

        foreach ($rolePermissionsMap as $roleName => $permissionNames) {
            $roleId = $roles[$roleName] ?? null;
            if (!$roleId) continue;

            foreach ($permissionNames as $permName) {
                $permId = is_int($permName) ? $permName : ($permissions[$permName] ?? null);
                if (!$permId) continue;

                $pivotInsert[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                ];
            }
        }

        DB::table('role_has_permissions')->insertOrIgnore($pivotInsert);
    }
}
