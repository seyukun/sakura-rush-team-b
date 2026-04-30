<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Container extends Model
{
    use HasFactory;

    // 連番のIDではないことを指定
    public $incrementing = false;

    // 主キーのデータ型が文字列であることを指定
    protected $keyType = 'string';

    // 変更可能なカラムを指定（セキュリティ対策: 複数代入の許可）
    protected $fillable = [
        'id', 'user_id', 'ip', 'cpu_quota_ms', 'cpu_period_ms', 
        'mem_m', 'volume_size', 'sftp_port', 'sftp_password'
    ];

    /**
     * IPアドレスの自動変換（アクセサ / ミューテタ）
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function ip(): Attribute
    {
        return Attribute::make(
            get: fn (int|null $value) => $value !== null ? long2ip($value) : null, // DBから取得時: 整数 -> IP文字列
            set: fn (string|null $value) => $value !== null ? ip2long(explode('/', $value)[0]) : null, // DBへ保存時: IP文字列(CIDR付きも考慮) -> 整数
        );
    }
}
