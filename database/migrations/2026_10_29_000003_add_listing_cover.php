<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $t) {
            $t->string('cover_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $t) {
            $t->dropColumn('cover_path');
        });
    }
};
