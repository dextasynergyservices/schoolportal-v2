<?php

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
        Schema::table('notices', function (Blueprint $table) {
            $table->string('file_url', 500)->nullable()->after('image_public_id');
            $table->string('file_public_id')->nullable()->after('file_url');
            $table->string('file_name')->nullable()->after('file_public_id');
            $table->json('target_classes')->nullable()->after('target_levels');
            $table->string('status', 20)->default('approved')->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'file_public_id', 'file_name', 'target_classes', 'status']);
        });
    }
};
