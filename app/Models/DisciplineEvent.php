<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisciplineEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'semester_id', 'student_id', 'event_date', 'event_type',
        'violation_id', 'achievement_id', 'points_effect',
        'description', 'recorded_by', 'original_recorder_name', 'follow_up',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function violation()
    {
        return $this->belongsTo(Violation::class);
    }

    public function achievement()
    {
        return $this->belongsTo(Achievement::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
