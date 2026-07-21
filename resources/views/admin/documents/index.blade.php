@extends('layouts.ocrs')
@section('title', 'Document Verification')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.admin-sidebar', ['active' => 'documents'])
    <div class="card">
        <h2>Document Verification (Audit Trail Enabled)</h2>
        <table>
            <thead><tr><th>Student</th><th>Type</th><th>File</th><th>Status</th><th>Audits</th><th>Action</th></tr></thead>
            <tbody>
                @foreach($documents as $doc)
                    <tr>
                        <td>{{ $doc->studentProfile->user->name }}</td>
                        <td>{{ $doc->document_type }}</td>
                        <td>{{ $doc->original_filename }}</td>
                        <td>{{ $doc->status->label() }}</td>
                        <td>{{ $doc->audits->count() }}</td>
                        <td>
                            @if($doc->status->value === 'pending')
                                <form method="POST" action="{{ route('admin.documents.review', $doc) }}" style="display:inline;">@csrf<input type="hidden" name="action" value="verify"><button class="btn btn-primary" type="submit">Verify</button></form>
                                <form method="POST" action="{{ route('admin.documents.review', $doc) }}" style="display:inline;">@csrf<input type="hidden" name="action" value="reject"><input type="hidden" name="rejection_reason" value="Document unclear or invalid"><button class="btn btn-outline" type="submit">Reject</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        {{ $documents->links() }}
    </div>
</div>
@endsection
