<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenaltyDecision extends Model
{
    protected $fillable = [
        'doc_uuid',
        'docid',
        'party_name',
        'penalty_authority',
        'penalty_type',
        'penalty_basis'
    ];
}
