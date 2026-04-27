<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50)->unique();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('user_id'); // usersテーブルのid (INT UNSIGNED) に合わせる
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_domains');
    }
};
