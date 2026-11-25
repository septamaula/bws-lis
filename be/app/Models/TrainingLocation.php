<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'training_locations';

    protected $fillable = [
        'code',
        'name',
        'type',
        'building',
        'floor',
        'capacity',
        'facilities',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
    ];
}
