<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'employee_number', 'name', 'phone', 'email', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function homeroomClasses()
    {
        return $this->hasMany(ClassRoom::class, 'homeroom_teacher_id');
    }
}
