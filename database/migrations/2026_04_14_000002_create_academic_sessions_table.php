<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->enum('status', ['upcoming', 'active', 'completed'])->default('upcoming');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['school_id', 'is_current']);
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->tinyInteger('term_number');
            $table->string('name', 50);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_current')->default(false);
            $table->enum('status', ['upcoming', 'active', 'completed'])->default('upcoming');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['session_id', 'term_number'], 'unique_session_term');
            $table->index(['school_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_sessions');
    }
};
