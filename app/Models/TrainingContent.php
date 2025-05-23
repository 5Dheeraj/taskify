<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id', 'title', 'content_type', 'media_url', 'description'
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
