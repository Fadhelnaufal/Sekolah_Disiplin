<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SemesterSnapshot extends Model
{
    protected $fillable = [
        'semester_id',
        'student_id',
        'class_room_id',
        'violation_points',
        'achievement_points',
        'net_points',
        'notes',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }
}
