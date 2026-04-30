<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_emails', function (Blueprint $table) {
            // Store [{name, path, mime, size}] objects for attached files
            $table->json('attachments')->nullable()->after('body');
            // When set, indicates email was dispatched to queue (not sent synchronously)
            $table->timestamp('queued_at')->nullable()->after('sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('platform_emails', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'queued_at']);
        });
    }
};
