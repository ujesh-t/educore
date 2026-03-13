<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id',
        'class_id',
        'parent_id',
        'parent_name',
        'parent_phone',
        'parent_email',
        'guardian_name',
        'guardian_phone',
        'emergency_contact',
        'medical_info',
        'admission_date',
        'graduation_year',
        'status',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'graduation_year' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasManyThrough(
            Assignment::class,
            ClassModel::class,
            'id', // Foreign key on classes table
            'class_id', // Foreign key on assignments table
            'class_id', // Local key on students table
            'id' // Local key on classes table
        );
    }
}
