<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'semester_id', 'type', 'letter_number',
        'subject', 'generated_at', 'generated_by', 'status',
        'pdf_path', 'metadata',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
