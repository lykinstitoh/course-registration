<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Registrar = 'registrar';
    case Finance = 'finance';
    case AcademicStaff = 'academic_staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Registrar => 'Registrar',
            self::Finance => 'Finance Officer',
            self::AcademicStaff => 'Academic Staff',
            self::Admin => 'System Administrator',
        };
    }

    public function isStaff(): bool
    {
        return $this !== self::Student;
    }
}
