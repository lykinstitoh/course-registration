<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'admission_number',
        'national_id',
        'kcse_mean_grade',
        'kcse_index_number',
        'kcse_year',
        'date_of_birth',
        'gender',
        'county',
        'address',
        'next_of_kin_name',
        'next_of_kin_phone',
        'consent_data_processing',
        'consent_given_at',
    ];

    protected function casts(): array
    {
        return [
            'kcse_mean_grade' => 'decimal:2',
            'date_of_birth' => 'date',
            'consent_data_processing' => 'boolean',
            'consent_given_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }
}
