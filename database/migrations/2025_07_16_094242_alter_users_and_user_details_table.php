<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("users", function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropColumn('role_id');
            }
        });

        Schema::table("user_details", function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'category')) {
                $table->dropColumn("category");
            }
        });
    }

    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role_id')) {
                $table->foreignId("role_id")
                      ->nullable()
                      ->constrained("roles")
                      ->cascadeOnDelete();
            }
        });

        Schema::table("user_details", function (Blueprint $table) {
            if (!Schema::hasColumn('user_details', 'category')) {
                $table->enum("category", ["STUD", "ASSOC", "PROF"]);
            }
        });
    }
};
