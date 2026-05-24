<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('policy_insights', function (Blueprint $table) {
            $table->id();
            $table->string('doc_uuid')->unique()->comment('统一RAG检索UUID');
            $table->string('title')->comment('政策新闻标题');
            $table->longText('content')->comment('新闻资讯全文内容');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_insights');
    }
};
