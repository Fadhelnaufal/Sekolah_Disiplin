<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FollowUp extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'discipline_event_id',
        'counseling_record_id',
        'title',
        'action_plan',
        'due_date',
        'status',
        'outcome',
        'assigned_to',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'is_overdue',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function disciplineEvent()
    {
        return $this->belongsTo(DisciplineEvent::class);
    }

    public function counselingRecord()
    {
        return $this->belongsTo(CounselingRecord::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }

        if (! $this->due_date) {
            return false;
        }

        return $this->due_date->isPast();
    }
}
