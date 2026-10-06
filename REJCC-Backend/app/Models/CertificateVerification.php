<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateVerification extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['certificate_id', 'methode', 'resultat', 'ip_hash', 'agent'];

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }
}
