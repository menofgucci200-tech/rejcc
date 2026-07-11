<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    /** Jalons standards du parcours d'incubation. */
    public const MILESTONES = ['Étude de marché', 'Business plan validé', 'Levée de fonds', 'Lancement pilote'];

    protected $fillable = [
        'user_id', 'title', 'description', 'members_count', 'status',
        'in_incubator', 'funding_goal', 'funding_raised', 'milestones',
    ];

    protected function casts(): array
    {
        return [
            'in_incubator' => 'boolean',
            'milestones' => 'array',
        ];
    }

    public function porteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function defaultMilestones(): array
    {
        return array_map(fn (string $label) => ['label' => $label, 'done' => false], self::MILESTONES);
    }
}
