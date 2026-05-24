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
        Schema::create('service_guides', function (Blueprint $table) {
            $table->id();
            $table->string('doc_uuid')->unique()->comment('统一RAG检索UUID');
            $table->string('title')->comment('标题');
            $table->string('item_name')->nullable()->comment('事项名称');
            $table->string('subitem_name')->nullable()->comment('二级事项名称');
            $table->string('st_id')->nullable()->comment('事项ID');
            $table->string('guide_id')->nullable()->comment('指南ID');
            $table->longText('application_materials')->nullable()->comment('申请材料及形式标准');
            $table->longText('rights_obligations')->nullable()->comment('权利义务');
            $table->longText('handling_procedures')->nullable()->comment('办理流程');
            $table->longText('establishment_basis')->nullable()->comment('设立依据');
            $table->longText('faq')->nullable()->comment('常见问题');
            $table->longText('approved_documents')->nullable()->comment('审批证件结果');
            $table->string('quantitative_restriction')->nullable()->comment('数量限制');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_guides');
    }
};
