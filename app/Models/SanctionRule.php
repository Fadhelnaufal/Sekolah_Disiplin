<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanctionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'level', 'min_points', 'max_points', 'action', 
        'executor', 'description', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
