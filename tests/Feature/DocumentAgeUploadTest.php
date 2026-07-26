<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentRequirement;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAgeUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->assertGreaterThanOrEqual(1, DocumentRequirement::where('code', 'national_id')->count());
        $this->assertGreaterThanOrEqual(1, DocumentRequirement::where('code', 'birth_certificate')->count());
    }

    public function test_adult_must_upload_national_id_not_birth_certificate(): void
    {
        $user = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create([
            'user_id' => $user->id,
            'date_of_birth' => now()->subYears(20)->toDateString(),
        ]);

        $this->assertSame('national_id', $profile->requiredIdentityDocumentCode());
        $this->assertTrue(
            $profile->getRequiredDocumentRequirements()->contains(fn ($r) => $r->code === 'national_id')
        );
        $this->assertFalse(
            $profile->getRequiredDocumentRequirements()->contains(fn ($r) => $r->code === 'birth_certificate')
        );

        $this->actingAs($user)->post(route('student.documents.store'), [
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->create('birth.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('document_type');

        $this->actingAs($user)->post(route('student.documents.store'), [
            'document_type' => 'national_id',
            'file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'student_profile_id' => $profile->id,
            'document_type' => 'national_id',
        ]);
    }

    public function test_minor_must_upload_birth_certificate_not_national_id(): void
    {
        $user = User::factory()->student()->create();
        $profile = StudentProfile::factory()->create([
            'user_id' => $user->id,
            'date_of_birth' => now()->subYears(17)->subMonths(3)->toDateString(),
        ]);

        $this->assertSame('birth_certificate', $profile->requiredIdentityDocumentCode());
        $this->assertTrue($profile->isUnderAdultIdentityAge());

        $this->actingAs($user)->post(route('student.documents.store'), [
            'document_type' => 'national_id',
            'file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('document_type');

        $this->actingAs($user)->post(route('student.documents.store'), [
            'document_type' => 'birth_certificate',
            'file' => UploadedFile::fake()->create('birth.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        Document::where('student_profile_id', $profile->id)->update([
            'status' => DocumentStatus::Verified,
            'verified_at' => now(),
        ]);

        // Other required docs still missing — identity alone is not enough
        $this->assertFalse($profile->fresh()->hasAllRequiredDocumentsVerified());

        foreach (['kcse_certificate', 'passport_photo'] as $code) {
            Document::create([
                'student_profile_id' => $profile->id,
                'document_type' => $code,
                'original_filename' => "{$code}.pdf",
                'stored_path' => "documents/{$code}.pdf",
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'status' => DocumentStatus::Verified,
                'verified_at' => now(),
            ]);
        }

        $this->assertTrue($profile->fresh()->hasAllRequiredDocumentsVerified());
    }

    public function test_identity_upload_blocked_until_date_of_birth_is_set(): void
    {
        $user = User::factory()->student()->create();
        StudentProfile::factory()->create([
            'user_id' => $user->id,
            'date_of_birth' => null,
        ]);

        $this->actingAs($user)->post(route('student.documents.store'), [
            'document_type' => 'national_id',
            'file' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('document_type');
    }
}
