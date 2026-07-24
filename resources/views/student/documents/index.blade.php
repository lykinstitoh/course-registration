@extends('layouts.ocrs')
@section('title', 'Documents')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'documents'])
    <div>
        <h1 style="color:var(--primary);margin-bottom:1rem;">Supporting Documents</h1>
        <p style="color:var(--muted);margin-bottom:1rem;">Upload anytime — verification runs in parallel with fee payment and admissions review.</p>

        @if(!empty($documentTypes))
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
                    <div class="form-group"><label>File (PDF/JPG/PNG, max 5MB)</label><input type="file" name="file" accept=".pdf,image/*" required></div>
                </div>
                <button class="btn btn-primary" type="submit">Upload</button>
            </form>
        </div>
        @else
        <div class="card" style="background:#dcfce7;color:#166534;">
            All listed document types have been uploaded. You can wait for verification while continuing with payments.
        </div>
        @endif

        <div class="card">
            <h3>Uploaded Documents</h3>
            <table>
                <thead><tr><th>Type</th><th>File</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td>{{ $doc->displayName() }}</td>
                            <td>{{ $doc->original_filename }}</td>
                            <td>{{ $doc->status->label() }}</td>
                            <td><a href="{{ route('student.documents.download', $doc) }}">Download</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No documents uploaded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
