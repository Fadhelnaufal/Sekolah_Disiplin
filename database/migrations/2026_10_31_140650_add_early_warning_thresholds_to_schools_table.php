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
        Schema::table('schools', function (Blueprint $table) {
            $table->unsignedInteger('attention_threshold')->default(20)->after('school_code');
            $table->unsignedInteger('early_warning_days')->default(30)->after('attention_threshold');
            $table->unsignedInteger('rapid_increase_threshold')->default(15)->after('early_warning_days');
            $table->unsignedInteger('highest_point_threshold')->default(150)->after('rapid_increase_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'attention_threshold',
                'early_warning_days',
                'rapid_increase_threshold',
                'highest_point_threshold',
            ]);
        });
    }
};
