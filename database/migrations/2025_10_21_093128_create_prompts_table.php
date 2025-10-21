<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('visibility', ['private','public','unlisted'])->default('private');
            $table->text('content');
            $table->json('meta')->nullable(); // es. lingua, modello target, note
            $table->timestamps();
            $table->index(['visibility','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('prompts'); }
};
