<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('kind');
            $t->string('title_vi');
            $t->string('title_ja');
            $t->text('body_vi');
            $t->text('body_ja');
            $t->string('location');
            $t->string('category');
            $t->string('price_vi')->nullable();
            $t->string('price_ja')->nullable();
            $t->boolean('published')->default(false);
            $t->timestamps();
        });
        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('service');
            $t->string('locale', 2);
            $t->text('message');
            $t->boolean('handled')->default(false);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('listings');
    }
};
