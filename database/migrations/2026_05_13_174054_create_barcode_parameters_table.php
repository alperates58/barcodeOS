<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barcode_type_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('key');
            $table->string('type');
            $table->text('default_value')->nullable();
            $table->text('min_value')->nullable();
            $table->text('max_value')->nullable();
            $table->json('options')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('available_features')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['barcode_type_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_parameters');
    }
};
