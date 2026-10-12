<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sanction_rules', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->integer('min_points');
            $table->integer('max_points')->nullable();
            $table->string('action');
            $table->string('executor');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanction_rules');
    }
};
