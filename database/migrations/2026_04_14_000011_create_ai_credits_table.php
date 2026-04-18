<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_credit_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchased_by')->constrained('users')->cascadeOnDelete();
            $table->integer('credits');
            $table->decimal('amount_naira', 10, 2);
            $table->string('reference', 100)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->index('school_id');
            $table->index(['school_id', 'status']);
        });

        Schema::create('ai_credit_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('school_levels')->cascadeOnDelete();
            $table->integer('allocated_credits')->default(0);
            $table->integer('used_credits')->default(0);
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->useCurrent();

            $table->unique(['school_id', 'level_id'], 'unique_school_level_allocation');
            $table->index('school_id');
        });

        Schema::create('ai_credit_usage_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('school_levels')->nullOnDelete();
            $table->enum('usage_type', ['quiz', 'game']);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->integer('credits_used')->default(1);
            $table->timestamp('created_at')->useCurrent();

            $table->index('school_id');
            $table->index(['school_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_credit_usage_log');
        Schema::dropIfExists('ai_credit_allocations');
        Schema::dropIfExists('ai_credit_purchases');
    }
};
