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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('name');
            $table->string('email');
            $table->string('state');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->enum('status', ['open', 'pending', 'closed'])->default('pending');
            $table->foreignId('assigned_to')->nullable()->references('id')->on('users');
            $table->foreignId('assigned_by')->nullable()->references('id')->on('users');
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->double('avg_response_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
