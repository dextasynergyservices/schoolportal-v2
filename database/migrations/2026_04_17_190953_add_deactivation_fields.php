<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('deactivation_reason')->nullable()->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('deactivation_reason');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->text('deactivation_reason')->nullable()->after('is_active');
            $table->timestamp('deactivated_at')->nullable()->after('deactivation_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['deactivation_reason', 'deactivated_at']);
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['deactivation_reason', 'deactivated_at']);
        });
    }
};
