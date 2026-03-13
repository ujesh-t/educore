<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'route_prefix',
        'is_core',
        'is_active',
        'is_free_module',
        'config',
        'sort_order',
    ];

    protected $casts = [
        'is_core' => 'boolean',
        'is_active' => 'boolean',
        'is_free_module' => 'boolean',
        'config' => 'array',
    ];

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_modules')
            ->withPivot(['is_enabled', 'config'])
            ->withTimestamps();
    }

    public function enabledSchools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_modules')
            ->wherePivot('is_enabled', true)
            ->withPivot(['is_enabled', 'config'])
            ->withTimestamps();
    }

    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFreeModules($query)
    {
        return $query->where('is_free_module', true);
    }

    public function scopeOrderBySort($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
