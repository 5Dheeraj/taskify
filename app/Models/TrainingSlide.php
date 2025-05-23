<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSlide extends Model
{
    protected $fillable = [
        'training_id',
        'workspace_id',
        'admin_id',
        'title',
        'description',      // This will be shown as content in slide
        'content_type',     // video, text, mixed
        'media_url',        // YouTube or PPT URL
        'order',
        'duration',         // optional: display in UI
        'drip_enabled',     // future use
        'visible_after',    // future use
    ];

    // Slide belongs to Training
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    // Slide created by Admin
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
