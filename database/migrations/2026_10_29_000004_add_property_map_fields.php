<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $t) {
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('city')->nullable();
            $t->unsignedBigInteger('price_yen')->nullable();
            $t->decimal('area_sqm', 8, 2)->nullable();
            $t->string('floor_plan', 30)->nullable();
            $t->string('station', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $t) => $t->dropColumn(['latitude', 'longitude', 'city', 'price_yen', 'area_sqm', 'floor_plan', 'station']));
    }
};
