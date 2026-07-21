<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    protected $fillable = [
        'course_unit_id',
        'semester_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'venue',
        'lecturer',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function courseUnit(): BelongsTo
    {
        return $this->belongsTo(CourseUnit::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
