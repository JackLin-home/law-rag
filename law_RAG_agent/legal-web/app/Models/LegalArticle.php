<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalArticle extends Model
{
    protected $fillable = [
        'doc_uuid',
        'url',
        'title',
        'publish_date',
        'content',
        'attachments',
        'crawled_at',
        'data_type',
        'source_module'
    ];

    // 自动将数据库中的 JSON 字符串转为 PHP 数组
    protected $casts = [
        'attachments' => 'array',
    ];
}
