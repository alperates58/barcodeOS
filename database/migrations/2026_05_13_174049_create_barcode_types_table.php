<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcode_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barcode_category_id')->constrained('barcode_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->string('icon')->nullable();
            $table->string('example_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->unsignedInteger('default_width')->nullable();
            $table->unsignedInteger('default_height')->nullable();
            $table->unsignedInteger('default_margin')->nullable();
            $table->string('default_format')->nullable();
            $table->json('supported_export_formats')->nullable();
            $table->json('required_features')->nullable();
            $table->json('parameter_schema')->nullable();
            $table->longText('documentation')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcode_types');
    }
};
