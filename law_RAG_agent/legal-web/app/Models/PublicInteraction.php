<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicInteraction extends Model
{
    protected $fillable = [
        'doc_uuid',
        'title',
        'consult_id',
        'consult_category',
        'consult_time',
        'reply_unit',
        'reply_time',
        'question',
        'answer'
    ];
}
