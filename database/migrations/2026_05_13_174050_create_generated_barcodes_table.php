<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barcode_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('web')->index();
            $table->text('input_preview')->nullable();
            $table->string('input_hash', 64)->index();
            $table->json('parameters')->nullable();
            $table->string('export_format')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('generated_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_barcodes');
    }
};
