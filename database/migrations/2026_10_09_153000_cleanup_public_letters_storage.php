<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (Storage::disk('public')->exists('letters')) {
            $files = Storage::disk('public')->files('letters');

            foreach ($files as $file) {
                try {
                    $sourceSize = Storage::disk('public')->size($file);

                    if (Storage::disk('local')->exists($file)) {
                        $targetSize = Storage::disk('local')->size($file);

                        if ($sourceSize === $targetSize) {
                            Storage::disk('public')->delete($file);
                            Log::info("Deleted public letter: {$file}");
                        } else {
                            Log::warning("File size mismatch for {$file}, skipping deletion.");
                        }
                    } else {
                        // Copy missing file and then delete
                        $content = Storage::disk('public')->get($file);
                        if (Storage::disk('local')->put($file, $content)) {
                            if (Storage::disk('local')->size($file) === $sourceSize) {
                                Storage::disk('public')->delete($file);
                                Log::info("Copied and deleted public letter: {$file}");
                            }
                        }
                    }
                } catch (Exception $e) {
                    Log::error("Failed processing file {$file} during cleanup: ".$e->getMessage());
                }
            }

            // Optionally delete directory if empty
            if (empty(Storage::disk('public')->files('letters'))) {
                Storage::disk('public')->deleteDirectory('letters');
            }
        }
    }

    public function down(): void
    {
        // No safe way to reverse this without duplicating files unnecessarily
    }
};
