<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('users', function(Blueprint $table) { $table->string('role')->default('staff'); $table->boolean('active')->default(true); });
  Schema::create('tasks', function(Blueprint $table) {
   $table->id(); $table->string('title'); $table->text('description')->nullable();
   $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->date('due_date')->nullable(); $table->string('status')->default('todo'); $table->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('tasks'); Schema::table('users', function(Blueprint $table) { $table->dropColumn(['role','active']); }); }
};

