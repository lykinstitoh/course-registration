@extends('layouts.ocrs')
@section('title', 'New Application')
@section('nav')<form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline" type="submit">Logout</button></form>@endsection
@section('content')
<div class="container portal">
    @include('partials.student-sidebar', ['active' => 'applications'])
    <div class="card">
        <h2>Programme Application</h2>
        <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 1rem;">
            Ages {{ $minAge }}+ for diploma/certificate and {{ $degreeMinAge }}+ for degree programmes.
            Applicants under 18 must provide a parent or guardian. KCSE index numbers are normally 11 digits (KNEC).
        </p>
        <form method="POST" action="{{ route('student.applications.store') }}" enctype="multipart/form-data">
            @csrf

            <h3 class="mt-4">Personal & Contact Information</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label>Date of Birth <span aria-hidden="true">*</span></label>
                    <input
                        type="date"
                        name="date_of_birth"
                        value="{{ old('date_of_birth', $profile->date_of_birth?->format('Y-m-d')) }}"
                        min="{{ $dobMin }}"
                        max="{{ $dobMax }}"
                        required
                    >
                    <small style="color:#6b7280;">Must be between {{ $dobMin }} and {{ $dobMax }} (age {{ $minAge }}–{{ $maxAge }}).</small>
                </div>
                <div class="form-group">
                    <label>Gender <span aria-hidden="true">*</span></label>
                    <select name="gender" required>
                        <option value="">Select gender</option>
                        <option value="Male" @selected(old('gender', $profile->gender) === 'Male')>Male</option>
                        <option value="Female" @selected(old('gender', $profile->gender) === 'Female')>Female</option>
                        <option value="Other" @selected(old('gender', $profile->gender) === 'Other')>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Next of Kin / Guardian Name</label>
                    <input type="text" name="next_of_kin_name" value="{{ old('next_of_kin_name', $profile->next_of_kin_name) }}" maxlength="255">
                    <small style="color:#6b7280;">Required if you are under 18.</small>
                </div>
                <div class="form-group">
                    <label>Next of Kin / Guardian Phone</label>
                    <input
                        type="tel"
                        name="next_of_kin_phone"
                        value="{{ old('next_of_kin_phone', $profile->next_of_kin_phone) }}"
                        placeholder="07XX XXX XXX"
                        maxlength="20"
                    >
                </div>
                <div class="form-group">
                    <label>Employment Details (Optional)</label>
                    <input type="text" name="employment_details" value="{{ old('employment_details', $profile->employment_details) }}" placeholder="Company, Role, etc." maxlength="500">
                </div>
            </div>

            <h3 class="mt-4">Academic Qualifications</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label>KCSE Mean Grade (points) <span aria-hidden="true">*</span></label>
                    <input
                        type="number"
                        step="1"
                        min="1"
                        max="12"
                        name="kcse_mean_grade"
                        value="{{ old('kcse_mean_grade', $profile->kcse_mean_grade) }}"
                        required
                    >
                    <small style="color:#6b7280;">Points scale: A=12 … C+=7 … E=1 (CUE degree floor is typically C+/7).</small>
                </div>
                <div class="form-group">
                    <label>KCSE Index Number <span aria-hidden="true">*</span></label>
                    <input
                        type="text"
                        name="kcse_index_number"
                        value="{{ old('kcse_index_number', $profile->kcse_index_number) }}"
                        inputmode="numeric"
                        pattern="{{ '[0-9]{'.$kcseIndexMin.','.$kcseIndexMax.'}' }}"
                        minlength="{{ $kcseIndexMin }}"
                        maxlength="{{ $kcseIndexMax }}"
                        title="Enter {{ $kcseIndexMin }}–{{ $kcseIndexMax }} digits (usually 11)"
                        required
                    >
                </div>
                <div class="form-group">
                    <label>KCSE Year <span aria-hidden="true">*</span></label>
                    <select name="kcse_year" required>
                        <option value="">Select KCSE examination year</option>
                        @foreach($kcseYears as $year)
                            <option value="{{ $year }}" @selected((string) old('kcse_year', $profile->kcse_year) === (string) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label id="identity-number-label">
                        @if($identityDocumentCode === 'birth_certificate')
                            Birth Certificate No. <span aria-hidden="true">*</span>
                        @else
                            National ID / Passport No. <span aria-hidden="true">*</span>
                        @endif
                    </label>
                    <input
                        type="text"
                        name="national_id"
                        id="identity-number-input"
                        value="{{ old('national_id', $profile->national_id) }}"
                        maxlength="30"
                        title="National ID (7–9 digits), Maisha (14 digits), passport, or birth certificate number"
                        required
                    >
                    <small id="identity-number-hint" style="color:#6b7280;">
                        @if($identityDocumentCode === 'birth_certificate')
                            Under {{ $adultAgeThreshold }}: enter your birth certificate number and upload the certificate below.
                        @elseif($identityDocumentCode === 'national_id')
                            Age {{ $adultAgeThreshold }}+: enter your National ID or passport number and upload that document below.
                        @else
                            Enter date of birth first — under {{ $adultAgeThreshold }} uses a Birth Certificate; {{ $adultAgeThreshold }}+ uses a National ID.
                        @endif
                    </small>
                </div>
                <div class="form-group">
                    <label>County <span aria-hidden="true">*</span></label>
                    <select name="county" required>
                        <option value="">Select county</option>
                        @foreach($counties as $county)
                            <option value="{{ $county }}" @selected(old('county', $profile->county) === $county)>{{ $county }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h3 class="mt-4">Course & Campus Selection</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label>Programme <span aria-hidden="true">*</span></label>
                    <select name="programme_id" required>
                        <option value="">Select programme</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (Min KCSE: {{ $p->minimum_kcse_grade }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Intake <span aria-hidden="true">*</span></label>
                    <select name="intake_id" required>
                        <option value="">Select intake</option>
                        @foreach($intakes as $i)
                            <option value="{{ $i->id }}">{{ $i->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Campus</label>
                    <select name="campus_id">
                        <option value="">Select campus</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}">{{ $campus->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($missingDocuments->isNotEmpty() || $optionalDocuments->isNotEmpty())
            <h3 class="mt-4">Supporting Documents</h3>
            <p style="font-size: 0.9rem; color: #6b7280; margin-bottom: 1rem;">
                Upload now or later — payment and admissions review are not blocked by pending verification.
                Allowed formats: PDF, JPG, PNG (Max 5MB).
                Identity document depends on age: Birth Certificate if under {{ $adultAgeThreshold }}, National ID / Passport at {{ $adultAgeThreshold }}+.
            </p>
            @if(! $identityDocumentCode)
            <p style="font-size: 0.9rem; color: #9a3412; margin-bottom: 1rem;">
                Enter your date of birth above so we can show the correct identity document upload.
            </p>
            @endif
            @if($missingDocuments->isNotEmpty())
            <div class="grid-2" id="required-documents">
                @foreach($missingDocuments as $doc)
                <div class="form-group" data-doc-code="{{ $doc->code }}">
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
<script>
(function () {
    const dobInput = document.querySelector('input[name="date_of_birth"]');
    const label = document.getElementById('identity-number-label');
    const hint = document.getElementById('identity-number-hint');
    const threshold = {{ (int) $adultAgeThreshold }};
    if (!dobInput || !label || !hint) return;

    function ageFromDob(value) {
        if (!value) return null;
        const dob = new Date(value + 'T00:00:00');
        if (Number.isNaN(dob.getTime())) return null;
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        return age;
    }

    function syncIdentityCopy() {
        const age = ageFromDob(dobInput.value);
        if (age === null) {
            label.innerHTML = 'National ID / Birth Certificate No. <span aria-hidden="true">*</span>';
            hint.textContent = 'Enter date of birth first — under ' + threshold + ' uses a Birth Certificate; ' + threshold + '+ uses a National ID.';
            return;
        }
        if (age < threshold) {
            label.innerHTML = 'Birth Certificate No. <span aria-hidden="true">*</span>';
            hint.textContent = 'Under ' + threshold + ': enter your birth certificate number and upload the certificate below.';
        } else {
            label.innerHTML = 'National ID / Passport No. <span aria-hidden="true">*</span>';
            hint.textContent = 'Age ' + threshold + '+: enter your National ID or passport number and upload that document below.';
        }
    }

    dobInput.addEventListener('change', syncIdentityCopy);
    syncIdentityCopy();
})();
</script>
@endsection
