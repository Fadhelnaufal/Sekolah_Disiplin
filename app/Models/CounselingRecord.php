<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounselingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id', 'student_id', 'counselor_id', 'original_recorder_name', 
        'date_scheduled', 'date_completed', 'category', 
        'reason', 'summary', 'follow_up_action', 
        'status', 'confidentiality_level', 'created_by', 'updated_by'
    ];

    protected $casts = [
        'date_scheduled' => 'date',
        'date_completed' => 'date',
        'reason' => 'encrypted',
        'summary' => 'encrypted',
        'follow_up_action' => 'encrypted',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}
