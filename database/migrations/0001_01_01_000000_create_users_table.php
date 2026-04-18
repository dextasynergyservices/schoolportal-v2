<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('custom_domain')->nullable()->unique();
            $table->string('logo_url', 500)->nullable();
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->default('Nigeria');
            $table->string('website')->nullable();
            $table->string('motto')->nullable();
            $table->integer('ai_free_credits')->default(15);
            $table->integer('ai_purchased_credits')->default(0);
            $table->date('ai_free_credits_reset_at')->nullable();
            $table->integer('ai_credits_total_purchased')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('custom_domain');
            $table->index('is_active');
        });

        Schema::create('school_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('slug', 50);
            $table->tinyInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['school_id', 'slug'], 'unique_school_level');
            $table->index('school_id');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('username', 100);
            $table->string('password');
            $table->enum('role', ['super_admin', 'school_admin', 'teacher', 'student', 'parent']);
            $table->foreignId('level_id')->nullable()->constrained('school_levels')->nullOnDelete();
            $table->string('avatar_url', 500)->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'username'], 'unique_school_username');
            $table->unique(['school_id', 'email'], 'unique_school_email');
            $table->index('school_id');
            $table->index(['school_id', 'role']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('school_levels');
        Schema::dropIfExists('schools');
    }
};
