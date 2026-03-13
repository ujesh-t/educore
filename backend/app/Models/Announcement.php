<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'created_by',
        'target_roles',
        'target_class_ids',
        'priority',
        'publish_at',
        'expires_at',
        'is_active',
        'is_pinned',
        'view_count',
    ];

    protected $casts = [
        'target_roles' => 'array',
        'target_class_ids' => 'array',
        'publish_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_pinned' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
