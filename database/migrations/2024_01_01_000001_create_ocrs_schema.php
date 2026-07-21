<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('role')->default('student');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('department');
            $table->string('award_level'); // certificate, diploma, degree, masters
            $table->unsignedSmallInteger('duration_semesters');
            $table->decimal('minimum_kcse_grade', 4, 2); // e.g. 5.50 for C+
            $table->text('cue_accreditation_ref')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('intakes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. September 2026
            $table->string('academic_year', 9); // e.g. 2026/2027
            $table->date('application_opens');
            $table->date('application_closes');
            $table->date('registration_opens')->nullable();
            $table->date('registration_closes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intake_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Semester 1, Semester 2
            $table->unsignedTinyInteger('sequence');
            $table->date('registration_deadline');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('admission_number')->nullable()->unique();
            $table->string('national_id', 20)->nullable();
            $table->decimal('kcse_mean_grade', 4, 2)->nullable();
            $table->string('kcse_index_number', 30)->nullable();
            $table->year('kcse_year')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('county')->nullable();
            $table->text('address')->nullable();
            $table->string('next_of_kin_name')->nullable();
            $table->string('next_of_kin_phone', 20)->nullable();
            $table->boolean('consent_data_processing')->default(false);
            $table->timestamp('consent_given_at')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->constrained();
            $table->foreignId('intake_id')->constrained();
            $table->string('status')->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('course_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->unsignedTinyInteger('credit_units');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedTinyInteger('semester_level'); // recommended semester
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('programme_course_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_unit_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_core')->default(true);
            $table->unique(['programme_id', 'course_unit_id']);
        });

        Schema::create('course_unit_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prerequisite_id')->constrained('course_units')->cascadeOnDelete();
            $table->unique(['course_unit_id', 'prerequisite_id']);
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('intake_id')->constrained()->cascadeOnDelete();
            $table->string('fee_type'); // application, tuition, registration, exam
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained();
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('registration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_unit_id')->constrained();
            $table->unique(['registration_id', 'course_unit_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('KES');
            $table->string('method');
            $table->string('status')->default('pending');
            $table->string('mpesa_receipt')->nullable();
            $table->string('mpesa_checkout_request_id')->nullable();
            $table->string('bank_reference')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('document_type'); // kcse_certificate, national_id, passport_photo
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('status')->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('document_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->constrained('users');
            $table->string('action'); // uploaded, verified, rejected, re-uploaded
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained()->cascadeOnDelete();
            $table->string('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('venue');
            $table->string('lecturer')->nullable();
            $table->timestamps();
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_unit_id')->constrained();
            $table->foreignId('semester_id')->constrained();
            $table->string('grade', 5)->nullable();
            $table->decimal('marks', 5, 2)->nullable();
            $table->string('status')->default('pending'); // pending, published
            $table->timestamps();
            $table->unique(['student_profile_id', 'course_unit_id', 'semester_id']);
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel'); // sms, email
            $table->string('event'); // application_status, payment_confirmation, registration_deadline
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('status')->default('queued'); // queued, sent, failed
            $table->json('provider_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('results');
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('document_audits');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('registration_items');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('course_unit_prerequisites');
        Schema::dropIfExists('programme_course_unit');
        Schema::dropIfExists('course_units');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('intakes');
        Schema::dropIfExists('programmes');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
