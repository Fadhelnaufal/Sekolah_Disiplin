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
        Schema::table('discipline_events', function (Blueprint $table) {
            $table->string('original_recorder_name')->nullable()->after('recorded_by');
        });

        Schema::table('counseling_records', function (Blueprint $table) {
            $table->string('original_recorder_name')->nullable()->after('counselor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('counseling_records', function (Blueprint $table) {
            $table->dropColumn('original_recorder_name');
        });

        Schema::table('discipline_events', function (Blueprint $table) {
            $table->dropColumn('original_recorder_name');
        });
    }
};
