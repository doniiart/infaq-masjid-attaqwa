<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MosqueSetting extends Model
{
    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'bank_accounts',
        'about_description',
        'logo_path',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'bank_accounts' => 'array',
        ];
    }
}