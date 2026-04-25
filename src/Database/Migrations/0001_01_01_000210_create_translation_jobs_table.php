<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('source_language_id')->constrained('languages')->cascadeOnDelete();
            $table->foreignId('target_language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('provider')->default('deepl'); // deepl|google
            $table->string('status')->default('pending'); // pending|applied|rejected
            $table->json('translated_fields')->nullable(); // {field_identifier: translated_value}
            $table->timestamps();
            $table->timestamp('applied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_jobs');
    }
};
