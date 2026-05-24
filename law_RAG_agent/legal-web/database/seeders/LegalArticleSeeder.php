<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegalArticleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('legal_articles')->insert([
            [
                'doc_id' => 'LAW-CIV-0001',
                'file_name' => '中华人民共和国民法典',
                'publish_unit' => '全国人民代表大会',
                'publish_date' => '2020-05-28',
                'effective_date' => '2021-01-01',
                'is_valid' => true,
                'applicable_fields' => json_encode(['民事', '合同']),
                'chapter' => '第三编 合同',
                'article' => '第五百零二条',
                'paragraph' => '第一款',
                'content' => '依法成立的合同，自成立时生效，但是法律另有规定或者当事人另有约定的除外。',
                'sync_vector' => 0,
                'sync_graph' => 0,
                'created_at' => now(),
            ],
            [
                'doc_id' => 'INT-CIV-0005',
                'file_name' => '最高人民法院关于适用《中华人民共和国民法典》合同编通则部分的解释',
                'publish_unit' => '最高人民法院',
                'publish_date' => '2023-12-04',
                'effective_date' => '2023-12-05',
                'is_valid' => true,
                'applicable_fields' => json_encode(['司法解释', '合同']),
                'chapter' => '第一部分 一般规定',
                'article' => '第三条',
                'paragraph' => '第二款',
                'content' => '当事人约定法律、行政法规规定应当办理批准等手续生效的合同，由一方负责办理手续，另一方未予配合的，人民法院可以判决由该方履行办理手续的义务。',
                'sync_vector' => 0,
                'sync_graph' => 0,
                'created_at' => now(),
            ],
            [
                'doc_id' => 'CASE-2024-001',
                'file_name' => '某科技公司与某贸易公司买卖合同纠纷案',
                'publish_unit' => 'XX市中级人民法院',
                'publish_date' => '2024-01-15',
                'effective_date' => '2024-01-15',
                'is_valid' => true,
                'applicable_fields' => json_encode(['案例', '买卖合同']),
                'chapter' => '判决书正文',
                'article' => '裁判理由',
                'paragraph' => '段落三',
                'content' => '法院认为，被告未按约定支付货款，构成违约。虽然合同未明确约定逾期利息，但根据民法典相关规定，原告有权主张逾期付款利息。',
                'sync_vector' => 0,
                'sync_graph' => 0,
                'created_at' => now(),
            ]
        ]);
    }
}
