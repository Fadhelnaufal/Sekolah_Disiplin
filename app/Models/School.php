<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'npsn', 'city', 'phone',
        'principal_name', 'vice_principal_name', 'counseling_officer_name', 'application_name'
    ];

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
