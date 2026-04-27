<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('containers', function (Blueprint $table) {
            // id: char(10), Primary Key
            $table->char('id', 10)->primary();
            
            // user_id: int unsigned
            $table->unsignedInteger('user_id');
            
            // ip: int unsigned, Unique
            $table->unsignedInteger('ip')->unique();
            
            // nullが許可されているカラム
            $table->unsignedInteger('cpu_quota_ms')->nullable();
            $table->unsignedInteger('cpu_period_ms')->nullable();
            $table->unsignedInteger('mem_m')->nullable();
            $table->unsignedInteger('volume_size')->nullable();
            
            // SFTP情報
            $table->unsignedInteger('sftp_port');
            $table->string('sftp_password', 255);
            
            // Laravel標準の created_at と updated_at
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('containers');
    }
};
