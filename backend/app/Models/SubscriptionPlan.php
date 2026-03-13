<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'modules',
        'trial_days',
        'is_custom',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'modules' => 'array',
        'metadata' => 'array',
        'is_custom' => 'boolean',
        'is_active' => 'boolean',
        'trial_days' => 'integer',
    ];

    public function schools()
    {
        return $this->hasMany(Subscription::class, 'plan', 'key');
    }

    public function getActiveSchoolsCountAttribute()
    {
        return $this->schools()->where('status', 'active')->count();
    }

    public function scopeCustom($query)
    {
        return $query->where('is_custom', true);
    }

    public function scopePredefined($query)
    {
        return $query->where('is_custom', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
