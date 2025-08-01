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
            $table->dateTime('reviewed_at')->nullable();
            $table->dateTime('approval_requested_at')->nullable();
            $table->enum('status', [
                'unverified',
                'pending',
                'verified',
                'suspended',
                'in-review',
                'pending-approval'
            ])->default('unverified')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->dropColumn('reviewed_at');
            $table->dropColumn('approval_requested_at');
            $table->enum('status', [
                'unverified',
                'pending',
                'verified',
                'suspended'
            ])->default('unverified')->change();
        });
    }
};
