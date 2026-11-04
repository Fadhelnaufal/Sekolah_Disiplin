<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id', 'nis', 'name', 'class_room_id',
        'parent_name', 'parent_phone', 'status', 'notes',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function disciplineEvents()
    {
        return $this->hasMany(DisciplineEvent::class);
    }

    public function counselingRecords()
    {
        return $this->hasMany(CounselingRecord::class);
    }

    public function letters()
    {
        return $this->hasMany(Letter::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }
}
