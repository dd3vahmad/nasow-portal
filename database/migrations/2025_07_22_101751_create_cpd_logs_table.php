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
        Schema::create('cpd_logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description')->nullable();
            $table->foreignId('member_id')->references('id')->on('users');
            $table->foreignId('activity_id')->nullable()->references('id')->on('cpd_activities');
            $table->date('completed_at');
            $table->decimal('credit_hours', 4, 1);
            $table->string('certificate_url')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpd_logs');
    }
};
