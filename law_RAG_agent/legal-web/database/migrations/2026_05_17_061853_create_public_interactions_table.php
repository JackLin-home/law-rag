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
        Schema::create('public_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('doc_uuid')->unique()->comment('统一RAG检索UUID');
            $table->string('title')->comment('咨询标题');
            $table->string('consult_id')->nullable()->comment('咨询ID');
            $table->string('consult_category')->nullable()->comment('咨询分类');
            $table->string('consult_time')->nullable()->comment('咨询时间');
            $table->string('reply_unit')->nullable()->comment('答复单位');
            $table->string('reply_time')->nullable()->comment('回复时间');
            $table->longText('question')->comment('群众提问内容');
            $table->longText('answer')->comment('官方答复内容');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_interactions');
    }
};
