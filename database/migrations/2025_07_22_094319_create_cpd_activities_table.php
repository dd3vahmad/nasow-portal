<?php

use App\Enums\CPDActivityType;
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
        Schema::create('cpd_activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description');
            $table->enum('type', array_map(fn($case) => $case->value, CpdActivityType::cases()));
            $table->decimal('credit_hours', 4, 1);
            $table->string('hosting_body')->default('NASOW');
            $table->string('certificate_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cpd_activities');
    }
};
