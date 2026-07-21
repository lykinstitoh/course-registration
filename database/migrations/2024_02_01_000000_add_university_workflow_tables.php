<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group'); // e.g., general, auth, admission, payment, document, notification
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // mpesa, bank_deposit, card, cash
            $table->boolean('is_active')->default(true);
            $table->text('instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // national_id, kcse_slip, passport_photo
            $table->boolean('is_required')->default(true);
            $table->string('allowed_extensions')->default('pdf,jpg,png');
            $table->integer('max_size_kb')->default(2048);
            $table->timestamps();
        });

        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // draft, submitted, pending_review, more_info_required, approved, rejected, waitlisted
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // The user who made the change
            $table->timestamps();
        });

        Schema::create('admission_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('letter_path');
            $table->timestamp('generated_at');
            $table->timestamps();
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->text('employment_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn('employment_details');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropForeign(['campus_id']);
            $table->dropColumn('campus_id');
        });

        Schema::dropIfExists('campuses');
        Schema::dropIfExists('admission_letters');
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('document_requirements');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('system_settings');
    }
};
