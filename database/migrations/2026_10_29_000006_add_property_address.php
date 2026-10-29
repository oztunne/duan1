<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->string('address', 300)->nullable());
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn('address'));
    }
};
