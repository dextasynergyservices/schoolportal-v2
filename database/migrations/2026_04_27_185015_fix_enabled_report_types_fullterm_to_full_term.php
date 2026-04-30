<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix 'fullterm' → 'full_term' in enabled_report_types JSON column.
     * The grading config form was saving 'fullterm' but the rest of the codebase
     * (controllers, views, DB enum) uses 'full_term'.
     */
    public function up(): void
    {
        // Simple string replacement in JSON — 'fullterm' only appears as a value, never as a key
        DB::table('report_card_config')
            ->whereNotNull('enabled_report_types')
            ->where('enabled_report_types', 'like', '%fullterm%')
            ->update([
                'enabled_report_types' => DB::raw(
                    "REPLACE(enabled_report_types, '\"fullterm\"', '\"full_term\"')"
                ),
            ]);
    }

    public function down(): void
    {
        // No rollback needed — 'full_term' is the correct value
    }
};
