<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('image_url', 500)->nullable();
            $table->string('image_public_id')->nullable();
            $table->json('target_levels')->nullable();
            $table->json('target_roles')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->useCurrent();
            $table->date('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
