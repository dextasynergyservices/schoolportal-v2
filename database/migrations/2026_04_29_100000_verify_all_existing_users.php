<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * All users on this platform are created by admins, not through self-registration.
 * Email verification has no meaning here, so we mark every existing unverified user
 * as verified and ensure every future user is auto-verified on creation (see User::booted).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally a no-op: we cannot know which users were originally unverified.
    }
};
