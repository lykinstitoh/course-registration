<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Documents\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function index()
    {
        $profile = Auth::user()->studentProfile;
        $documents = $profile
            ->documents()
            ->with(['audits.performer', 'requirement'])
            ->latest()
            ->get();

        $requirements = $profile->getRequiredDocumentRequirements()
            ->concat($profile->getOptionalDocumentRequirements())
            ->unique('code')
            ->values();

        $uploadedCodes = $documents
            ->whereIn('status', [\App\Enums\DocumentStatus::Pending, \App\Enums\DocumentStatus::Verified])
            ->pluck('document_type')
            ->unique();

        $documentTypes = $requirements
            ->reject(fn ($req) => $uploadedCodes->contains($req->code))
            ->pluck('name', 'code')
            ->toArray();

        $identityCode = $profile->requiredIdentityDocumentCode();
        $needsDateOfBirth = blank($profile->date_of_birth);
        $adultAgeThreshold = $profile->adultAgeThreshold();

        return view('student.documents.index', compact(
            'documents',
            'documentTypes',
            'requirements',
            'identityCode',
            'needsDateOfBirth',
            'adultAgeThreshold'
        ));
    }

    public function store(Request $request)
    {
        $profile = Auth::user()->studentProfile;
        $allowedCodes = $profile->getRequiredDocumentRequirements()
            ->concat($profile->getOptionalDocumentRequirements())
            ->pluck('code')
            ->unique()
            ->values()
            ->all();

        $data = $request->validate([
            'document_type' => ['required', 'string', Rule::in($allowedCodes)],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        if ($error = $profile->identityDocumentUploadError($data['document_type'])) {
            throw ValidationException::withMessages([
                'document_type' => $error,
            ]);
        }

        if (! $profile->canUploadDocumentType($data['document_type'])) {
            throw ValidationException::withMessages([
                'document_type' => 'This document type is not available for your age profile.',
            ]);
        }

        $applicationId = $profile->applications()->latest()->value('id');

        $this->documentService->upload(
            $profile,
            $request->file('file'),
            $data['document_type'],
            $applicationId,
            Auth::user()
        );

        return back()->with('success', 'Document uploaded successfully. Verification runs in parallel — you can continue with fees and enrollment.');
    }

    public function download(Document $document)
    {
        if ($document->student_profile_id !== Auth::user()->studentProfile->id) {
            abort(403);
        }

        $path = $this->documentService->getSecurePath($document);
        if (! $path) {
            return back()->with('error', 'Document file not found.');
        }

        return response()->download($path, $document->original_filename);
    }
}
