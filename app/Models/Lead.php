<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Lead extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'kind', 'status', 'assigned_user_id',
        'name', 'email', 'phone', 'source',
        'scheduled_at', 'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public const KIND_CONTACT = 'contact';
    public const KIND_ORG = 'org';
    public const KIND_APPLICATION = 'application';

    public const STATUSES = ['new', 'contacted', 'scheduled', 'qualified', 'won', 'lost'];

    /**
     * Auto-stamp a UUID on create so the admin UI can route by it without
     * leaking sequential IDs.
     */
    protected static function booted(): void
    {
        static::creating(function (Lead $lead): void {
            if (empty($lead->uuid)) {
                $lead->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Admin routes bind by UUID — sequential IDs stay internal-only.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function contactMessage(): HasOne
    {
        return $this->hasOne(ContactMessage::class);
    }

    public function orgInquiry(): HasOne
    {
        return $this->hasOne(OrgInquiry::class);
    }

    public function application(): HasOne
    {
        return $this->hasOne(Application::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class)->latest();
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class)->orderByDesc('scheduled_at');
    }
}
