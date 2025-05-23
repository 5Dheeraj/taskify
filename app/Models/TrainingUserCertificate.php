<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingUserCertificate extends Model
{
    protected $fillable = [
        'training_id',
        'user_id',
        'certificate_url',
        'issued_at'
    ];

    protected $casts = [
        'issued_at' => 'datetime',
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
