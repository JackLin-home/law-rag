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
        Schema::create('complaint_publicities', function (Blueprint $table) {
            $table->id();
            $table->string('doc_uuid')->unique()->comment('统一RAG检索UUID');
            $table->string('enterprise_name')->nullable()->comment('涉事企业名称');
            $table->string('city_code')->nullable()->comment('行政区划代码');
            $table->string('issue_type')->nullable()->comment('问题类型');
            $table->string('case_type')->nullable()->comment('投诉举报类别');
            $table->string('accept_dept')->nullable()->comment('受理部门');
            $table->string('reg_time')->nullable()->comment('登记时间');
            $table->string('end_time')->nullable()->comment('办结时间');
            $table->string('public_time')->nullable()->comment('公示时间');
            $table->longText('process_result')->comment('处理结果');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaint_publicities');
    }
};
