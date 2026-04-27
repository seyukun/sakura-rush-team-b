<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
