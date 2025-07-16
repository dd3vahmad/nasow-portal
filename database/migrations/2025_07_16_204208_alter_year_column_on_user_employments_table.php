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
        Schema::table('user_employments', static function(Blueprint $table) {
            if (Schema::hasColumn('user_employments', 'year')) {
                $table->dropColumn('year');
            }
            $table->integer('year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_employments', static function(Blueprint $table) {
            if (Schema::hasColumn('user_employments', 'year')) {
                $table->dropColumn('year');
            }
            $table->dateTime('year');
        });
    }
};
