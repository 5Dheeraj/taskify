<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingQuiz extends Model
{
    protected $fillable = [
        'training_id',
        'question',
        'question_type', // mcq or true_false
        'options',        // JSON encoded for MCQs
        'correct_answer', // string or value for evaluation
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
