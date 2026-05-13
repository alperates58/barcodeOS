<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('feature_key')->index();
            $table->string('period_type')->index();
            $table->timestamp('period_start')->index();
            $table->timestamp('period_end')->nullable();
            $table->unsignedBigInteger('used')->default(0);
            $table->unsignedBigInteger('limit')->nullable();
            $table->string('source')->default('web')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'feature_key', 'period_type', 'period_start', 'source'], 'usage_counters_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
