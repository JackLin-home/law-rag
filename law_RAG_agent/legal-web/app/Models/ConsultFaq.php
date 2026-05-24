<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultFaq extends Model
{
    protected $fillable = [
        'doc_uuid',
        'title',
        'content',
        'consult_category'
    ];
}
