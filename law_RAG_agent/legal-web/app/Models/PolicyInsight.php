<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyInsight extends Model
{
    protected $fillable = [
        'doc_uuid',
        'title',
        'content'
    ];
}
