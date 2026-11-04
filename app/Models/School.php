<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'npsn', 'city', 'phone',
        'principal_name', 'vice_principal_name', 'counseling_officer_name', 'application_name',
        'province', 'postal_code', 'email', 'website',
        'principal_nip', 'signer_title', 'letter_city',
        'classification_code', 'school_code',
        'logo_path', 'signature_path', 'stamp_path',
        'template_style',
        'show_npsn', 'show_phone', 'show_email', 'show_website',
        'show_signature_image', 'show_stamp',
        'attention_threshold', 'early_warning_days',
        'rapid_increase_threshold', 'highest_point_threshold',
    ];

    protected $casts = [
        'show_npsn' => 'boolean',
        'show_phone' => 'boolean',
        'show_email' => 'boolean',
        'show_website' => 'boolean',
        'show_signature_image' => 'boolean',
        'show_stamp' => 'boolean',
        'attention_threshold' => 'integer',
        'early_warning_days' => 'integer',
        'rapid_increase_threshold' => 'integer',
        'highest_point_threshold' => 'integer',
    ];

    public function getLogoSrcAttribute(): ?string
    {
        return $this->resolveFileToDataUri($this->logo_path);
    }

    public function getSignatureSrcAttribute(): ?string
    {
        return $this->resolveFileToDataUri($this->signature_path);
    }

    public function getStampSrcAttribute(): ?string
    {
        return $this->resolveFileToDataUri($this->stamp_path);
    }

    protected function resolveFileToDataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'data:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $localPath = public_path(ltrim($path, '/'));
        if (file_exists($localPath)) {
            $mime = mime_content_type($localPath) ?: 'image/png';
            $data = file_get_contents($localPath);

            return 'data:'.$mime.';base64,'.base64_encode($data);
        }

        $storagePath = storage_path('app/public/'.ltrim(str_replace('/storage/', '', $path), '/'));
        if (file_exists($storagePath)) {
            $mime = mime_content_type($storagePath) ?: 'image/png';
            $data = file_get_contents($storagePath);

            return 'data:'.$mime.';base64,'.base64_encode($data);
        }

        return $path;
    }

    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
