<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingProgress extends Model
{
    use HasFactory;

    // ✅ Important: Define the correct table name
    protected $table = 'training_progresses';

    // ✅ Allow mass assignment on these fields
    protected $fillable = [
        'workspace_id',
        'admin_id',
        'training_id',
        'content_id',
        'status',
    ];
}
