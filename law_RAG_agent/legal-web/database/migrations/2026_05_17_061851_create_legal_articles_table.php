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
        Schema::create('legal_articles', function (Blueprint $table) {
            $table->id();
            $table->string('doc_uuid')->unique()->comment('统一RAG检索UUID');
            $table->text('url')->nullable()->comment('原文链接');
            $table->string('title')->comment('法律法规名称');
            $table->string('publish_date')->nullable()->comment('发布日期');
            $table->longText('content')->comment('法律全文正文');
            $table->json('attachments')->nullable()->comment('附件列表');
            $table->string('crawled_at')->nullable()->comment('爬取时间');
            $table->string('data_type')->nullable()->comment('数据类型');
            $table->string('source_module')->nullable()->comment('来源模块');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_articles');
    }
};
