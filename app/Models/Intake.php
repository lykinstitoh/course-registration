<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Intake extends Model
{
    protected $fillable = [
        'name',
        'academic_year',
        'application_opens',
        'application_closes',
        'registration_opens',
        'registration_closes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'application_opens' => 'date',
            'application_closes' => 'date',
            'registration_opens' => 'date',
            'registration_closes' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function feeStructures(): HasMany
    {
        return $this->hasMany(FeeStructure::class);
    }
}
