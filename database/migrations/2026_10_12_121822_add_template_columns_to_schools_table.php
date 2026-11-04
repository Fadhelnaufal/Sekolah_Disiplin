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
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('principal_nip')->nullable();
            $table->string('signer_title')->nullable()->default('Kepala Sekolah');
            $table->string('letter_city')->nullable();
            $table->string('classification_code')->nullable()->default('421.3');
            $table->string('school_code')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('stamp_path')->nullable();
            $table->string('template_style')->nullable()->default('formal_indonesia');
            $table->boolean('show_npsn')->default(true);
            $table->boolean('show_phone')->default(true);
            $table->boolean('show_email')->default(true);
            $table->boolean('show_website')->default(false);
            $table->boolean('show_signature_image')->default(true);
            $table->boolean('show_stamp')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'province',
                'postal_code',
                'email',
                'website',
                'principal_nip',
                'signer_title',
                'letter_city',
                'classification_code',
                'school_code',
                'logo_path',
                'signature_path',
                'stamp_path',
                'template_style',
                'show_npsn',
                'show_phone',
                'show_email',
                'show_website',
                'show_signature_image',
                'show_stamp',
            ]);
        });
    }
};
