<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('vin', 17)->nullable()->unique()->after('slug');
            $table->unsignedSmallInteger('year')->nullable()->after('vin');
            $table->unsignedTinyInteger('doors')->nullable();
            $table->unsignedTinyInteger('seats')->nullable();
            $table->unsignedTinyInteger('cylinders')->nullable();
            $table->decimal('displacement_l', 3, 1)->nullable();
            $table->unsignedSmallInteger('horsepower')->nullable();
            $table->string('fuel_type')->nullable();
            $table->string('airbags')->nullable();
            $table->string('tpms')->nullable();
            // Raw answer of the VIN lookup, kept so nothing has to be fetched twice.
            $table->json('vin_data')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropUnique(['vin']);
            $table->dropColumn([
                'vin', 'year', 'doors', 'seats', 'cylinders', 'displacement_l',
                'horsepower', 'fuel_type', 'airbags', 'tpms', 'vin_data',
            ]);
        });
    }
};
