<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = [
        'workspace_id',
        'admin_id',
        'title',
        'description',
        'level',
        'course_type', // 🔄 was 'type' earlier; use 'type' if you need old field
        'category',
        'subcategory',
        'language',
        'drip_enabled',
        'faq',
        'requirements',
        'outcomes',
        'status',
        'start_date',
        'completion_deadline',
        'assigned_roles',   // ✅ New
        'assigned_users',   // ✅ New
    ];

    protected $casts = [
        'assigned_roles' => 'array',
        'assigned_users' => 'array',
    ];
}