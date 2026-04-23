<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform-wide announcements from super admin to all schools
        Schema::create('platform_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['info', 'warning', 'critical'])->default('info');
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        // Tracks which schools have read a platform announcement
        Schema::create('platform_announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('platform_announcements')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('read_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');

            $table->unique(['announcement_id', 'school_id']);
        });

        // School-level announcements from school admin to teachers/students/parents
        Schema::create('school_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['info', 'warning', 'critical'])->default('info');
            $table->json('target_roles')->nullable(); // ['teacher','student','parent'] or null = all
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
        });

        // Tracks which users have dismissed a school announcement
        Schema::create('school_announcement_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained('school_announcements')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('dismissed_at');

            $table->unique(['announcement_id', 'user_id']);
        });

        // Log of emails sent by super admin to schools
        Schema::create('platform_emails', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('body'); // rich HTML content
            $table->json('recipient_school_ids'); // array of school IDs sent to
            $table->integer('total_recipients')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->foreignId('sent_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_emails');
        Schema::dropIfExists('school_announcement_dismissals');
        Schema::dropIfExists('school_announcements');
        Schema::dropIfExists('platform_announcement_reads');
        Schema::dropIfExists('platform_announcements');
    }
};
