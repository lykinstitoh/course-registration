@extends('layouts.ocrs')
@section('title', 'Documents')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'documents'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Academic Credentials</h1>
        <div class="card">
            <h3>Upload Document</h3>
            <form method="POST" action="{{ route('student.documents.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="grid-2">
                    <div class="form-group"><label>Document Type</label>
                        <select name="document_type" required>
                            @foreach($documentTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>File (PDF/JPG/PNG, max 5MB)</label><input type="file" name="file" required></div>
                </div>
                <button class="btn btn-primary" type="submit">Upload</button>
            </form>
        </div>
        <div class="card">
            <h3>Uploaded Documents</h3>
            <table>
                <thead><tr><th>Type</th><th>File</th><th>Status</th><th>Audit Actions</th></tr></thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td>{{ $documentTypes[$doc->document_type] ?? $doc->document_type }}</td>
                            <td>{{ $doc->original_filename }}</td>
                            <td>{{ $doc->status->label() }}</td>
                            <td>{{ $doc->audits->count() }} recorded</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No documents uploaded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
