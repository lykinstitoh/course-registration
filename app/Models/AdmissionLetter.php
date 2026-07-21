<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_profile_id',
        'application_id',
        'letter_path',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function studentProfile()
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
