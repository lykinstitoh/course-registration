<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseUnit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'credit_units',
        'capacity',
        'semester_level',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function programmes(): BelongsToMany
    {
        return $this->belongsToMany(Programme::class, 'programme_course_unit')
            ->withPivot('is_core');
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            CourseUnit::class,
            'course_unit_prerequisites',
            'course_unit_id',
            'prerequisite_id'
        );
    }

    public function registrationItems(): HasMany
    {
        return $this->hasMany(RegistrationItem::class);
    }

    public function timetableEntries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class);
    }

    public function enrolledCount(int $semesterId): int
    {
        return RegistrationItem::query()
            ->where('course_unit_id', $this->id)
            ->whereHas('registration', fn ($q) => $q
                ->where('semester_id', $semesterId)
                ->whereIn('status', ['submitted', 'confirmed']))
            ->count();
    }
}
