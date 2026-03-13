<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'subdomain',
        'domain',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'timezone',
        'logo',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($school) {
            if (empty($school->code)) {
                $school->code = strtoupper('SCH-' . uniqid());
            }
        });
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->latest();
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'school_modules')
            ->withPivot(['is_enabled', 'config'])
            ->withTimestamps();
    }

    public function enabledModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'school_modules')
            ->wherePivot('is_enabled', true)
            ->withPivot(['is_enabled', 'config'])
            ->withTimestamps();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }

    public function isEnabledModule(string $moduleKey): bool
    {
        $module = Module::where('key', $moduleKey)->first();
        if (!$module) {
            return false;
        }

        // Core modules are always enabled
        if ($module->is_core) {
            return true;
        }

        return $this->modules()
            ->where('modules.id', $module->id)
            ->wherePivot('is_enabled', true)
            ->exists();
    }

    public function getEnabledModulesAttribute(): array
    {
        return $this->enabledModules()
            ->get(['key', 'name', 'icon', 'route_prefix'])
            ->toArray();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function isOnTrial(): bool
    {
        $subscription = $this->activeSubscription()->first();
        return $subscription && $subscription->trial_ends_at && $subscription->trial_ends_at->isFuture();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }
}
