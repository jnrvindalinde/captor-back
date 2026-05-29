<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory;

    public const STATUS_ONBOARDING = 'onboarding';
    public const STATUS_ACTIVE     = 'active';
    public const STATUS_ON_HOLD    = 'on_hold';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_CHURNED    = 'churned';

    public const STATUSES = [
        self::STATUS_ONBOARDING,
        self::STATUS_ACTIVE,
        self::STATUS_ON_HOLD,
        self::STATUS_COMPLETED,
        self::STATUS_CHURNED,
    ];

    public const PROGRAMS = [
        'study-abroad', 'scholarship', 'career-coaching', 'test-prep', 'org-partnership',
    ];

    protected $fillable = [
        'uuid', 'name', 'email', 'phone', 'program', 'consultant_id', 'status',
        'start_date', 'next_milestone_label', 'next_milestone_due_at',
        'satisfaction', 'source_lead_id',
    ];

    protected $casts = [
        'start_date'            => 'datetime',
        'next_milestone_due_at' => 'datetime',
        'satisfaction'          => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (! $client->uuid) {
                $client->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consultant_id');
    }

    public function sourceLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'source_lead_id');
    }
}
