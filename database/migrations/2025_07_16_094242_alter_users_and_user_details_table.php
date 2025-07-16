<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn("role_id");
        });

        Schema::table("user_details", function (Blueprint $table) {
            $table->dropColumn("category");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->foreignId("role_id")->references("id")->on("users");
        });

        Schema::table("user_details", function (Blueprint $table) {
            $table->enum("category", ["STUD", "ASSOC", "PROF"]);
        });
    }
};
