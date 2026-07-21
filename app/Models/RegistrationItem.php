<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationItem extends Model
{
    protected $fillable = [
        'registration_id',
        'course_unit_id',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function courseUnit(): BelongsTo
    {
        return $this->belongsTo(CourseUnit::class);
    }
}
