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
        Schema::table('counseling_records', function (Blueprint $table) {
            $table->dropColumn(['counseling_date', 'point_snapshot', 'problem_description', 'action_taken', 'result', 'evaluation_date', 'source']);

            $table->date('date_scheduled')->nullable();
            $table->date('date_completed')->nullable();
            $table->string('category')->nullable();
            $table->text('reason')->nullable();
            $table->text('summary')->nullable(); // will be encrypted
            $table->text('follow_up_action')->nullable(); // will be encrypted
            $table->string('confidentiality_level')->default('normal');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('counseling_records', function (Blueprint $table) {
            $table->dropForeign(['counseling_records_created_by_foreign']);
            $table->dropForeign(['counseling_records_updated_by_foreign']);

            $table->dropColumn([
                'date_scheduled', 'date_completed', 'category', 'reason',
                'summary', 'follow_up_action', 'confidentiality_level', 'created_by', 'updated_by',
            ]);

            $table->date('counseling_date')->nullable();
            $table->integer('point_snapshot')->nullable();
            $table->text('problem_description')->nullable();
            $table->text('action_taken')->nullable();
            $table->string('result')->nullable();
            $table->date('evaluation_date')->nullable();
            $table->string('source')->nullable();
        });
    }
};
