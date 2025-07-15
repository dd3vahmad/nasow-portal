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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('other_name')->nullable();
            $table->enum('gender', ['MALE', 'FEMALE']);
            $table->date('dob');
            $table->string('specialization')->nullable();
            $table->string('address');
            $table->string('phone');
            $table->enum('category', ['PROF', 'ASSOC', 'STUD']);
            $table->enum('state', [
                'abia',
                'adamawa',
                'akwa_ibom',
                'anambra',
                'bauchi',
                'bayelsa',
                'benue',
                'borno',
                'cross_river',
                'delta',
                'ebonyi',
                'edo',
                'ekiti',
                'enugu',
                'gombe',
                'imo',
                'jigawa',
                'kaduna',
                'kano',
                'katsina',
                'kebbi',
                'kogi',
                'kwara',
                'lagos',
                'nasarawa',
                'niger',
                'ogun',
                'ondo',
                'osun',
                'oyo',
                'plateau',
                'rivers',
                'sokoto',
                'taraba',
                'yobe',
                'zamfara',
                'abuja'
            ]);
            $table->foreignId('user_id')->unique()->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_details');
    }
};
