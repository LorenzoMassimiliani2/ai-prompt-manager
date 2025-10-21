<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();     // es. chatgpt, gemini, claude
            $table->string('name');              // es. ChatGPT
            $table->string('base_url');          // es. https://chat.openai.com/
            $table->boolean('supports_query')->default(false); // se possiamo passare ?q=
            $table->json('meta')->nullable();    // es. {"icon_path":"...", "viewBox":"0 0 24 24"}
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('services'); }
};
