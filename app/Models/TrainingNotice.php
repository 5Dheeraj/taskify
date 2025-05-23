<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingNotice extends Model
{
    protected $fillable = [
        'training_id',
        'title',
        'message',
        'posted_by',
        'visibility_scope', // all / role-based
        'posted_at'
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
