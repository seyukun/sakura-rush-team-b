<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * 複数代入を許可する属性（セキュリティ対策）
     * ここに書いたカラムだけがプログラムから直接保存・更新できます
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * 配列やJSONにしたときに隠す属性（パスワードが漏れないようにするため）
     */
    protected $hidden = [
        'password',
    ];

    /**
     * キャスト（データ型の自動変換）
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed', // 保存時に自動でハッシュ化してくれます
        ];
    }
}
