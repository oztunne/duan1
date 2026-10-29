<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $t) {
            $t->string('prefecture', 2)->nullable()->index();
            $t->string('district', 150)->nullable();
            $t->string('railway_line', 150)->nullable();
            $t->string('property_type', 30)->nullable();
            $t->unsignedSmallInteger('walk_minutes')->nullable();
            $t->unsignedSmallInteger('building_age')->nullable();
            $t->unsignedBigInteger('initial_cost_yen')->nullable();
            $t->boolean('apply_overseas')->default(false);
            $t->json('amenities')->nullable();
        });
        foreach (['osaka' => '27', 'tokyo' => '13', 'kyoto' => '26', 'kobe' => '28', 'fukuoka' => '40'] as $city => $pref) {
            DB::table('listings')->where('kind', 'property')->where('city', $city)->update(['prefecture' => $pref]);
        }
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $t) => $t->dropColumn(['prefecture', 'district', 'railway_line', 'property_type', 'walk_minutes', 'building_age', 'initial_cost_yen', 'apply_overseas', 'amenities']));
    }
};
