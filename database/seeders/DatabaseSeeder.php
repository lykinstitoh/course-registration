<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CourseUnit;
use App\Models\FeeStructure;
use App\Models\Intake;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'System Administrator',
            'email' => 'admin@ocrs.ac.ke',
            'phone' => '254700000001',
            'role' => UserRole::Admin,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Registrar Office',
            'email' => 'registrar@ocrs.ac.ke',
            'phone' => '254700000002',
            'role' => UserRole::Registrar,
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'Finance Office',
            'email' => 'finance@ocrs.ac.ke',
            'phone' => '254700000003',
            'role' => UserRole::Finance,
            'password' => Hash::make('password'),
        ]);

        $programmes = [
            ['code' => 'BSC-CS', 'name' => 'BSc Computer Science', 'department' => 'Computing', 'award_level' => 'degree', 'duration_semesters' => 8, 'minimum_kcse_grade' => 7.00, 'cue_accreditation_ref' => 'CUE/REF/CS/2024'],
            ['code' => 'BBA', 'name' => 'Bachelor of Business Administration', 'department' => 'Business', 'award_level' => 'degree', 'duration_semesters' => 8, 'minimum_kcse_grade' => 6.00, 'cue_accreditation_ref' => 'CUE/REF/BBA/2024'],
            ['code' => 'DIP-IT', 'name' => 'Diploma in Information Technology', 'department' => 'Computing', 'award_level' => 'diploma', 'duration_semesters' => 4, 'minimum_kcse_grade' => 5.00, 'cue_accreditation_ref' => 'CUE/REF/DIT/2024'],
        ];

        foreach ($programmes as $p) {
            Programme::create($p + ['is_active' => true]);
        }

        $intake = Intake::create([
            'name' => 'September 2026',
            'academic_year' => '2026/2027',
            'application_opens' => '2026-05-01',
            'application_closes' => '2026-08-31',
            'registration_opens' => '2026-09-01',
            'registration_closes' => '2026-09-15',
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'intake_id' => $intake->id,
            'name' => 'Semester 1',
            'sequence' => 1,
            'registration_deadline' => '2026-09-15',
            'starts_on' => '2026-09-20',
            'ends_on' => '2027-01-15',
            'is_active' => true,
        ]);

        $units = [
            ['code' => 'CS101', 'name' => 'Introduction to Programming', 'credit_units' => 3, 'capacity' => 60, 'semester_level' => 1],
            ['code' => 'CS102', 'name' => 'Data Structures', 'credit_units' => 3, 'capacity' => 50, 'semester_level' => 2],
            ['code' => 'BUS101', 'name' => 'Principles of Management', 'credit_units' => 3, 'capacity' => 80, 'semester_level' => 1],
            ['code' => 'MATH101', 'name' => 'Discrete Mathematics', 'credit_units' => 3, 'capacity' => 70, 'semester_level' => 1],
        ];

        foreach ($units as $u) {
            CourseUnit::create($u + ['is_active' => true]);
        }

        $cs = Programme::where('code', 'BSC-CS')->first();
        $cs->courseUnits()->attach([
            1 => ['is_core' => true],
            2 => ['is_core' => true],
            4 => ['is_core' => true],
        ]);

        CourseUnit::find(2)->prerequisites()->attach(1);

        foreach (Programme::all() as $programme) {
            FeeStructure::create([
                'programme_id' => $programme->id,
                'intake_id' => $intake->id,
                'fee_type' => 'application',
                'description' => 'Application Fee',
                'amount' => 2000,
                'is_mandatory' => true,
            ]);
            FeeStructure::create([
                'programme_id' => $programme->id,
                'intake_id' => $intake->id,
                'fee_type' => 'tuition',
                'description' => 'Semester 1 Tuition',
                'amount' => 85000,
                'is_mandatory' => true,
            ]);
        }

        TimetableEntry::create([
            'course_unit_id' => 1,
            'semester_id' => $semester->id,
            'day_of_week' => 'Monday',
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'venue' => 'Lab 1',
            'lecturer' => 'Dr. Wanjiku Kamau',
        ]);

        \App\Models\Campus::create(['name' => 'Main Campus - Nairobi', 'code' => 'MC-NBI', 'location' => 'Nairobi CBD', 'is_active' => true]);
        \App\Models\Campus::create(['name' => 'Mombasa City Campus', 'code' => 'MSA-CC', 'location' => 'Mombasa Island', 'is_active' => true]);

        $settings = [
            // General
            ['group' => 'general', 'key' => 'institution_name', 'value' => 'OCRS University', 'type' => 'string'],
            ['group' => 'general', 'key' => 'institution_code', 'value' => 'OCRS', 'type' => 'string'],
            ['group' => 'general', 'key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean'],
            
            // Admissions
            ['group' => 'admission', 'key' => 'require_kcse_verification', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'admission', 'key' => 'auto_generate_admission_number', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'admission', 'key' => 'allow_late_applications', 'value' => '0', 'type' => 'boolean'],

            // Auth
            ['group' => 'auth', 'key' => 'require_email_verification', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'auth', 'key' => 'require_sms_verification', 'value' => '0', 'type' => 'boolean'],

            // Payment
            ['group' => 'payment', 'key' => 'enable_mpesa', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'payment', 'key' => 'enable_bank_transfer', 'value' => '1', 'type' => 'boolean'],
        ];

        foreach ($settings as $setting) {
            \App\Models\SystemSetting::create($setting);
        }
    }
}
