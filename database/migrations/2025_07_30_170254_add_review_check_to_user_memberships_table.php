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
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->boolean('reviewed')->default(false);
            $table->foreignId('reviewed_by')->nullable()->references('id')->on('users')->onDelete('set null');
            $table->string('comment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->dropColumn('reviewed');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn('comment');
        });
    }
};
