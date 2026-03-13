<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'subject_specialization',
        'qualifications',
        'experience_years',
        'hire_date',
        'termination_date',
        'status',
        'salary',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classes(): HasMany
    {
        return $this->user->classes();
    }

    public function subjects(): HasMany
    {
        return $this->user->subjects();
    }

    public function assignments(): HasMany
    {
        return $this->user->assignments();
    }

    public function grades(): HasMany
    {
        return $this->user->grades();
    }

    public function exams(): HasMany
    {
        return $this->user->exams();
    }
}
