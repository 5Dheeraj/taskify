<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingAccess extends Model
{
    protected $fillable = [
        'training_id',
        'role_id',
        'user_id',
        'workspace_id',
        'admin_id',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function role()
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
