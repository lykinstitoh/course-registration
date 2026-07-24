<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $requirements = [
            [
                'name' => 'KCSE Certificate / Result Slip',
                'code' => 'kcse_certificate',
                'is_required' => true,
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_size_kb' => 5120,
            ],
            [
                'name' => 'National ID / Passport',
                'code' => 'national_id',
                'is_required' => true,
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_size_kb' => 5120,
            ],
            [
                'name' => 'Passport-size Photo',
                'code' => 'passport_photo',
                'is_required' => true,
                'allowed_extensions' => 'jpg,jpeg,png',
                'max_size_kb' => 2048,
            ],
            [
                'name' => 'Birth Certificate (alternative ID for applicants without National ID)',
                'code' => 'birth_certificate',
                'is_required' => false,
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_size_kb' => 5120,
            ],
            [
                'name' => 'Degree / Diploma Certificate (postgraduate / credit transfer)',
                'code' => 'degree_certificate',
                'is_required' => false,
                'allowed_extensions' => 'pdf,jpg,jpeg,png',
                'max_size_kb' => 5120,
            ],
        ];

        foreach ($requirements as $requirement) {
            $exists = DB::table('document_requirements')->where('code', $requirement['code'])->exists();
            if (! $exists) {
                DB::table('document_requirements')->insert($requirement + [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Align enrollment deposit with common Kenyan private-institution practice
        DB::table('system_settings')
            ->where('key', 'min_tuition_percentage')
            ->where('value', '100')
            ->update(['value' => '50', 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('document_requirements')->whereIn('code', [
            'kcse_certificate',
            'national_id',
            'passport_photo',
            'birth_certificate',
            'degree_certificate',
        ])->delete();
    }
};
