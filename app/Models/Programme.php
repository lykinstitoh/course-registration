<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Programme extends Model
{
    protected $fillable = [
        'code',
        'name',
        'department',
        'award_level',
        'duration_semesters',
        'minimum_kcse_grade',
        'cue_accreditation_ref',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'minimum_kcse_grade' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function courseUnits(): BelongsToMany
    {
        return $this->belongsToMany(CourseUnit::class, 'programme_course_unit')
            ->withPivot('is_core');
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }
}
