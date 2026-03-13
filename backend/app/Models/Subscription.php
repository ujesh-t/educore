<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'subscription_plan_id',
        'plan',
        'status',
        'amount',
        'currency',
        'billing_cycle',
        'trial_ends_at',
        'starts_at',
        'expires_at',
        'cancelled_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function planModel(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && (!$this->expires_at || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    public function getPlanNameAttribute(): string
    {
        // If we have a plan model, use its name
        if ($this->planModel) {
            return $this->planModel->name;
        }

        return match($this->plan) {
            'free' => 'Free',
            'basic' => 'Basic',
            'standard' => 'Standard',
            'premium' => 'Premium',
            'custom' => 'Custom',
            default => ucfirst($this->plan),
        };
    }

    public function getIncludedModulesAttribute(): array
    {
        // If we have a plan model, use its modules
        if ($this->planModel && $this->planModel->modules) {
            return $this->planModel->modules;
        }

        // Fallback to predefined plans
        return match($this->plan) {
            'free' => ['dashboard', 'communication', 'profile'],
            'basic' => ['dashboard', 'communication', 'profile', 'students', 'attendance', 'academics'],
            'standard' => ['dashboard', 'communication', 'profile', 'students', 'attendance', 'academics', 'examinations', 'fees', 'transport', 'hostel'],
            'premium' => ['dashboard', 'communication', 'profile', 'students', 'attendance', 'academics', 'examinations', 'fees', 'transport', 'hostel', 'library', 'inventory', 'payroll', 'reports'],
            default => ['dashboard', 'communication', 'profile'],
        };
    }
}
