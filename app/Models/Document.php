<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    protected $fillable = [
        'student_profile_id',
        'application_id',
        'document_type',
        'original_filename',
        'stored_path',
        'mime_type',
        'file_size',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_type', 'code');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function displayName(): string
    {
        return $this->requirement?->name
            ?? config('ocrs.document_types.'.$this->document_type)
            ?? str_replace('_', ' ', ucfirst($this->document_type));
    }

    public function audits(): HasMany
    {
        return $this->hasMany(DocumentAudit::class);
    }
}
