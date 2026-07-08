<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnershipRequest extends Model
{
    protected $fillable = [
        'organisation', 'contact', 'email', 'telephone', 'type', 'message', 'statut',
    ];
}
