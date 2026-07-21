<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Documents\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function index()
    {
        $documents = Auth::user()->studentProfile
            ->documents()
            ->with('audits.performer')
            ->latest()
            ->get();

        $documentTypes = config('ocrs.document_types');

        return view('student.documents.index', compact('documents', 'documentTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', 'string'],
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $this->documentService->upload(
            Auth::user()->studentProfile,
            $request->file('file'),
            $data['document_type'],
            null,
            Auth::user()
        );

        return back()->with('success', 'Document uploaded successfully. Awaiting verification.');
    }
}
