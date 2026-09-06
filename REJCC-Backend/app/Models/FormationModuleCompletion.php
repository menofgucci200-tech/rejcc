<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormationModuleCompletion extends Model
{
    public $timestamps = false;

    protected $fillable = ['formation_enrollment_id', 'formation_module_id', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(FormationEnrollment::class, 'formation_enrollment_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(FormationModule::class, 'formation_module_id');
    }
}
