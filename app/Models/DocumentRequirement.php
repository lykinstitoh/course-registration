<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_required',
        'allowed_extensions',
        'max_size_kb',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'max_size_kb' => 'integer',
    ];
}
