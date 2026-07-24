@extends('layouts.ocrs')
@section('title', 'New Application')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'applications'])
    <div class="card">
        <h2>Programme Application</h2>
        <form method="POST" action="{{ route('student.applications.store') }}" enctype="multipart/form-data">
            @csrf
            
            <h3 class="mt-4">Personal & Contact Information</h3>
            <div class="grid-2">
                <div class="form-group"><label>Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"></div>
                <div class="form-group"><label>Gender</label>
                    <select name="gender">
                        <option value="">Select gender</option>
                        <option value="Male" @selected(old('gender') === 'Male')>Male</option>
                        <option value="Female" @selected(old('gender') === 'Female')>Female</option>
                        <option value="Other" @selected(old('gender') === 'Other')>Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Next of Kin Name</label><input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name') }}"></div>
                <div class="form-group"><label>Next of Kin Phone</label><input type="text" name="next_of_kin_phone" value="{{ old('next_of_kin_phone') }}"></div>
                <div class="form-group"><label>Employment Details (Optional)</label><input type="text" name="employment_details" value="{{ old('employment_details') }}" placeholder="Company, Role, etc."></div>
            </div>

            <h3 class="mt-4">Academic Qualifications</h3>
            <div class="grid-2">
                <div class="form-group"><label>KCSE Mean Grade (points)</label><input type="number" step="0.01" name="kcse_mean_grade" value="{{ old('kcse_mean_grade') }}" required></div>
                <div class="form-group"><label>KCSE Index Number</label><input type="text" name="kcse_index_number" value="{{ old('kcse_index_number') }}" inputmode="numeric" pattern="[0-9]*" maxlength="30" title="Enter numbers only" required></div>
                <div class="form-group"><label>KCSE Year</label>
                    <select name="kcse_year" required>
                        <option value="">Select KCSE examination year</option>
                        @foreach($kcseYears as $year)<option value="{{ $year }}" @selected((string) old('kcse_year') === (string) $year)>{{ $year }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>National ID / Birth Certificate No.</label><input type="text" name="national_id" value="{{ old('national_id', $profile->national_id) }}" maxlength="30" title="National ID digits, or birth certificate / passport number" required></div>
                <div class="form-group"><label>County</label>
                    <select name="county" required>
                        <option value="">Select county</option>
                        @foreach($counties as $county)<option value="{{ $county }}" @selected(old('county') === $county)>{{ $county }}</option>@endforeach
                    </select>
                </div>
            </div>

            <h3 class="mt-4">Course & Campus Selection</h3>
            <div class="grid-2">
                <div class="form-group"><label>Programme</label>
                    <select name="programme_id" required>
                        <option value="">Select programme</option>
                        @foreach($programmes as $p)<option value="{{ $p->id }}">{{ $p->name }} (Min KCSE: {{ $p->minimum_kcse_grade }})</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Intake</label>
                    <select name="intake_id" required>
                        <option value="">Select intake</option>
                        @foreach($intakes as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach
                    </select>
                </div>
                <div class="form-group"><label>Campus</label>
                    <select name="campus_id">
                        <option value="">Select campus</option>
                        @foreach($campuses as $campus)<option value="{{ $campus->id }}">{{ $campus->name }}</option>@endforeach
                    </select>
                </div>
            </div>

            @if($missingDocuments->isNotEmpty() || $optionalDocuments->isNotEmpty())
            <h3 class="mt-4">Supporting Documents</h3>
            <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 1rem;">
                Upload now or later — payment and admissions review are not blocked by pending verification.
                Allowed formats: PDF, JPG, PNG (Max 5MB). Applicants without a National ID may upload a Birth Certificate instead.
            </p>
            @if($missingDocuments->isNotEmpty())
            <div class="grid-2">
                @foreach($missingDocuments as $doc)
                <div class="form-group">
                    <label>{{ $doc->name }}</label>
                    <input type="file" name="documents[{{ $doc->code }}]" accept=".pdf,image/*">
                </div>
                @endforeach
            </div>
            @endif
            @if($optionalDocuments->isNotEmpty())
            <p style="font-size: 0.85rem; color: #6b7280; margin: 1rem 0 .5rem;">Optional</p>
            <div class="grid-2">
                @foreach($optionalDocuments as $doc)
                <div class="form-group">
                    <label>{{ $doc->name }}</label>
                    <input type="file" name="documents[{{ $doc->code }}]" accept=".pdf,image/*">
                </div>
                @endforeach
            </div>
            @endif
            @endif

            <div class="mt-4" style="display: flex; gap: 10px;">
                <button class="btn btn-secondary" type="submit" name="action" value="draft" formnovalidate>Save as Draft</button>
                <button class="btn btn-accent" type="submit" name="action" value="submit">Submit Application</button>
            </div>
        </form>
    </div>
</div>
@endsection
