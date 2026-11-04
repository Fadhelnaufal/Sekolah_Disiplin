<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        // Move files from public/letters to letters (in local disk)
        if (Storage::disk('public')->exists('letters')) {
            $files = Storage::disk('public')->files('letters');
            foreach ($files as $file) {
                $content = Storage::disk('public')->get($file);
                Storage::disk('local')->put($file, $content);
            }
        }
    }

    public function down(): void
    {
        // Reverse is not necessary since local is more secure,
        // but if we were to reverse:
        if (Storage::disk('local')->exists('letters')) {
            $files = Storage::disk('local')->files('letters');
            foreach ($files as $file) {
                $content = Storage::disk('local')->get($file);
                Storage::disk('public')->put($file, $content);
            }
        }
    }
};
