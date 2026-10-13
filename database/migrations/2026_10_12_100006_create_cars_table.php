<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('body_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('transmission_id')->constrained()->restrictOnDelete();
            $table->foreignId('drivetrain_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('trim')->nullable();
            $table->string('slug')->unique();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('mileage')->nullable();
            $table->string('color')->nullable();
            $table->string('engine')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('price_drop')->default(false);
            $table->timestamp('sold_at')->nullable();
            $table->unsignedInteger('sold_price')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
            $table->index(['is_active', 'created_at']);
            $table->index('sold_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
