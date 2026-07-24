<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Documents\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $requirements = \App\Models\DocumentRequirement::orderByDesc('is_required')->orderBy('name')->get();
        $uploadedCodes = $documents
            ->whereIn('status', [\App\Enums\DocumentStatus::Pending, \App\Enums\DocumentStatus::Verified])
            ->pluck('document_type')
            ->unique();

        $documentTypes = $requirements
            ->reject(fn ($req) => $uploadedCodes->contains($req->code))
            ->pluck('name', 'code')
            ->toArray();

        return view('student.documents.index', compact('documents', 'documentTypes', 'requirements'));
    }

    public function store(Request $request)
    {
        $validCodes = \App\Models\DocumentRequirement::pluck('code')->toArray();

        $data = $request->validate([
            'document_type' => ['required', 'string', 'in:'.implode(',', $validCodes)],
            'file' => ['required', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ]);

        $profile = Auth::user()->studentProfile;
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
