<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counseling_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('counseling_date');
            $table->integer('point_snapshot');
            $table->foreignId('counselor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('problem_description');
            $table->text('action_taken');
            $table->string('result')->nullable();
            $table->date('evaluation_date')->nullable();
            $table->string('source')->nullable();
            $table->string('status'); // open, monitoring, evaluated, closed, referred
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counseling_records');
    }
};
