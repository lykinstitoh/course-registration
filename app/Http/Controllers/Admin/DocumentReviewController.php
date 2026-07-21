<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Documents\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentReviewController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function index()
    {
        $documents = Document::with(['studentProfile.user', 'audits'])
            ->latest()
            ->paginate(20);

        return view('admin.documents.index', compact('documents'));
    }

    public function review(Request $request, Document $document)
    {
        $data = $request->validate([
            'action' => ['required', 'in:verify,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string'],
        ]);

        if ($data['action'] === 'verify') {
            $this->documentService->verify($document, Auth::user(), $request->input('notes'));
        } else {
            $this->documentService->reject($document, Auth::user(), $data['rejection_reason']);
        }

        return back()->with('success', 'Document review recorded with full audit trail.');
    }
}
