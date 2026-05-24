<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintPublicity extends Model
{
    protected $fillable = [
        'doc_uuid',
        'enterprise_name',
        'city_code',
        'issue_type',
        'case_type',
        'accept_dept',
        'reg_time',
        'end_time',
        'public_time',
        'process_result'
    ];
}
