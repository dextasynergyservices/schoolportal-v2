<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 50)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'action']);
            $table->index(['school_id', 'user_id']);
            $table->index('created_at');
        });

        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('from_class_id')->constrained('classes');
            $table->foreignId('to_class_id')->constrained('classes');
            $table->foreignId('from_session_id')->constrained('academic_sessions');
            $table->foreignId('to_session_id')->constrained('academic_sessions');
            $table->foreignId('promoted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('promoted_at')->useCurrent();

            $table->index(['school_id', 'to_session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
        Schema::dropIfExists('audit_logs');
    }
};
