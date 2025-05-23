<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingQuizResult extends Model
{
    protected $fillable = [
        'training_id',
        'user_id',
        'total_questions',
        'correct_answers',
        'score_percentage',
        'passed',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'passed' => 'boolean',
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
