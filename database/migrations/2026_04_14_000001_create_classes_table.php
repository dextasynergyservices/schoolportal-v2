<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('school_levels')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->tinyInteger('sort_order')->default(0);
            $table->integer('capacity')->nullable();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['school_id', 'level_id', 'slug'], 'unique_school_class');
            $table->index('school_id');
            $table->index(['school_id', 'level_id']);
            $table->index('teacher_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
