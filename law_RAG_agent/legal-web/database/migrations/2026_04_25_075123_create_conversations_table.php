<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // 关键：关联用户表，用户删除时对话也删除
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // 侧边栏显示的标题
            $table->string('title')->default('新对话');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
