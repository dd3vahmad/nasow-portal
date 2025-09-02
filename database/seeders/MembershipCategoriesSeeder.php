<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MembershipCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Student', 'slug' => 'STUD'],
            ['name' => 'Associate', 'slug' => 'ASSOC'],
            ['name' => 'Professional', 'slug' => 'PROF'],
        ];

        foreach ($categories as $category) {
            DB::table('membership_categories')->insert([
                'name'      => $category['name'],
                'slug'      => $category['slug'],
                'currency'  => 'NGN',
                'price'     => 0.00,
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);
        }
    }
}
