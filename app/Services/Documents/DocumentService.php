<?php

namespace App\Services\Documents;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentAudit;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentService
{
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    private const MAX_SIZE_KB = 5120;

    public function upload(
        StudentProfile $student,
        UploadedFile $file,
        string $documentType,
        ?int $applicationId = null,
        ?User $uploadedBy = null
    ): Document {
        $this->validateFile($file);

        $path = $file->store(
            "documents/{$student->id}/".Str::slug($documentType),
            'local'
        );

        $document = Document::create([
            'student_profile_id' => $student->id,
            'application_id' => $applicationId,
            'document_type' => $documentType,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => DocumentStatus::Pending,
        ]);

        $this->recordAudit($document, $uploadedBy ?? $student->user, 'uploaded', 'Document uploaded by student.');

        return $document;
    }

    public function verify(Document $document, User $staff, ?string $notes = null): Document
    {
        $document->update([
            'status' => DocumentStatus::Verified,
            'verified_by' => $staff->id,
            'verified_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->recordAudit($document, $staff, 'verified', $notes ?? 'Document verified by administration.');

        return $document->fresh();
    }

    public function reject(Document $document, User $staff, string $reason): Document
    {
        $document->update([
            'status' => DocumentStatus::Rejected,
            'verified_by' => $staff->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->recordAudit($document, $staff, 'rejected', $reason);

        return $document->fresh();
    }

    public function getSecurePath(Document $document): ?string
    {
        if (! Storage::disk('local')->exists($document->stored_path)) {
            return null;
        }

        return Storage::disk('local')->path($document->stored_path);
    }

    private function validateFile(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            throw new \InvalidArgumentException('Only PDF, JPEG, and PNG files are allowed.');
        }

        if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
            throw new \InvalidArgumentException('File size must not exceed 5 MB.');
        }
    }

    private function recordAudit(Document $document, User $user, string $action, ?string $notes): void
    {
        DocumentAudit::create([
            'document_id' => $document->id,
            'performed_by' => $user->id,
            'action' => $action,
            'notes' => $notes,
            'metadata' => [
                'document_type' => $document->document_type,
                'status' => $document->status->value,
            ],
        ]);
    }
}
