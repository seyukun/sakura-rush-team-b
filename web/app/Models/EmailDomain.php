<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'active',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean', // DBから取得した際に自動でtrue/falseに変換する
        ];
    }
}
