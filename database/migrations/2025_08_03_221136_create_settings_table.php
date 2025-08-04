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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('two_factor_enabled')->default(false);
            $table->boolean('email_notification')->default(true);
            $table->boolean('sms_notification')->default(false);
            $table->enum('color_mode', ['dark', 'light', 'system'])->default('system');
            $table->string('language')->default('english');
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
