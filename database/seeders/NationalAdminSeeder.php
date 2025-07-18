<?php

namespace Database\Seeders;

use App\Enums\RoleType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class NationalAdminSeeder extends Seeder
{
    /**
     * Run the first national admin seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::create([
                'name' => 'Gbeminiyi Adegbola',
                'email' => 'gbeminiyiadegbola@gmail.com',
                'reg_status' => 'done'
            ]);

            $user->credentials()->create([
                'email' => $user->email,
                'password' => '@Olamilekan1',
                'email_verified_at' => now(),
            ]);

            $role = Role::firstOrCreate(
                ['name' => RoleType::NationalAdmin->value],
                ['guard_name' => 'api']
            );

            $user->assignRole($role);

            $user->details()->create([
                'first_name' => 'Gbeminiyi',
                'last_name' => 'Adegbola',
                'gender' => 'MALE',
                'dob' => Date::create(1995, 4, 11),
                'address' => 'Alagbaka, Akure, Ondo State.',
                'phone' => '08163881377',
                'state' => 'Lagos',
            ]);
        });
    }
}
