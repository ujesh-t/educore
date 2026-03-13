<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class_id',
        'fee_type',
        'amount',
        'frequency',
        'academic_year',
        'term',
        'effective_from',
        'effective_until',
        'is_active',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'is_active' => 'boolean',
    ];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class, 'fee_structure_id');
    }
}
