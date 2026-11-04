<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'level', 'deduction_points',
        'description', 'frequency_rule', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function disciplineEvents()
    {
        return $this->hasMany(DisciplineEvent::class);
    }
}
