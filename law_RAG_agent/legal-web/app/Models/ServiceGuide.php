<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceGuide extends Model
{
    protected $fillable = [
        'doc_uuid',
        'title',
        'item_name',
        'subitem_name',
        'st_id',
        'guide_id',
        'application_materials',
        'rights_obligations',
        'handling_procedures',
        'establishment_basis',
        'faq',
        'approved_documents',
        'quantitative_restriction'
    ];
}
