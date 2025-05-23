<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAssignmentSubmission extends Model
{
    protected $fillable = [
        'training_id',
        'user_id',
        'assignment_id',
        'submission_text',
        'submission_file',
        'status',
        'submitted_at',
        'reviewed_at',
        'review_comments'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function assignment()
    {
        return $this->belongsTo(TrainingAssignment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
