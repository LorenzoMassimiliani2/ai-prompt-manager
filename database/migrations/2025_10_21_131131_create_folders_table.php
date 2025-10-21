<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('folders', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // proprietario
      $table->foreignId('parent_id')->nullable()->constrained('folders')->cascadeOnDelete();
      $table->string('name');
      $table->string('slug');
      $table->string('path')->index(); // es. /root/ai/marketing
      $table->unsignedInteger('depth')->default(0);
      $table->unsignedInteger('sort')->default(0);
      $table->timestamps();

      $table->unique(['user_id','parent_id','name']); // niente duplicati nello stesso livello per utente
    });
  }
  public function down(): void { Schema::dropIfExists('folders'); }
};
