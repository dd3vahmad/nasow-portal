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
        Schema::create('user_membership_renewals', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique();
            $table->string('transaction_id')->unique();
            $table->string('payment_channel')->nullable();
            $table->enum('category', ['STUD', 'ASSOC', 'PROF']);
            $table->decimal('amount');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->enum('status', ['PENDING', 'APPROVED', 'FAILED', 'ABANDONED'])->default('PENDING');
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_membership_renewals');
    }
};
