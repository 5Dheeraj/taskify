<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingUserStatus extends Model
{
    protected $fillable = [
        'training_id',
        'user_id',
        'completion_percentage',
        'status', // not_started, in_progress, completed
        'started_at',
        'completed_at',
        'last_viewed_at',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
