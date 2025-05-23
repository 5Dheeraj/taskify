<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAssignment extends Model
{
    protected $fillable = [
        'training_id',
        'instructions',
        'attachment_url',
        'deadline',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
