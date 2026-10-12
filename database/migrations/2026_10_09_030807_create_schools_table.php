<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->string('npsn')->nullable();
            $table->string('city');
            $table->string('phone')->nullable();
            $table->string('principal_name')->nullable();
            $table->string('counseling_officer_name')->nullable();
            $table->string('application_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
