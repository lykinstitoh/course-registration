<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    protected $fillable = [
        'programme_id',
        'intake_id',
        'fee_type',
        'description',
        'amount',
        'currency',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_mandatory' => 'boolean',
        ];
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(Intake::class);
    }
}
