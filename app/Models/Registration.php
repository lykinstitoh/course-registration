<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Registration extends Model
{
    protected $fillable = [
        'reference',
        'student_profile_id',
        'semester_id',
        'status',
        'submitted_at',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
            'submitted_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }
}
