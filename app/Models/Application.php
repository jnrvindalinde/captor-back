<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'lead_id',
        'status_self', 'status_other', 'location', 'field',
        'goal', 'goal_other', 'targets', 'timeline', 'budget',
        'story', 'newsletter',
        'decision', 'decision_note', 'decided_at', 'decided_by',
    ];

    protected $casts = [
        'targets' => 'array',
        'newsletter' => 'boolean',
        'decided_at' => 'datetime',
    ];

    public const DECISION_PENDING = 'pending';
    public const DECISION_APPROVED = 'approved';
    public const DECISION_DECLINED = 'declined';

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ApplicationFile::class);
    }
}
