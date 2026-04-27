<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain_id',
        'email',
        'password',
        'active',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'password' => 'hashed', // パスワードの自動ハッシュ化
        ];
    }
}
