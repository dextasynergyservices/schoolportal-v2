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
        Schema::table('report_card_config', function (Blueprint $table) {
            $table->string('principal_signature_url', 500)->nullable()->after('principal_title');
            $table->string('principal_signature_public_id', 255)->nullable()->after('principal_signature_url');
            $table->string('school_stamp_url', 500)->nullable()->after('principal_signature_public_id');
            $table->string('school_stamp_public_id', 255)->nullable()->after('school_stamp_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_card_config', function (Blueprint $table) {
            $table->dropColumn([
                'principal_signature_url',
                'principal_signature_public_id',
                'school_stamp_url',
                'school_stamp_public_id',
            ]);
        });
    }
};
