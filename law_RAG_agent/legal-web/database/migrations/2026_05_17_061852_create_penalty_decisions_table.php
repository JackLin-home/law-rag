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
        Schema::create('penalty_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('doc_uuid')->unique()->comment('统一RAG检索UUID');
            $table->string('docid')->nullable()->comment('决定书文号ID');
            $table->string('party_name')->comment('当事人/企业名称');
            $table->string('penalty_authority')->comment('处罚机关');
            $table->string('penalty_type')->comment('处罚类型');
            $table->longText('penalty_basis')->comment('处罚依据/法定条款');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalty_decisions');
    }
};
