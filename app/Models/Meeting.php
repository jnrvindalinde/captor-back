<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    use HasFactory;
    protected $fillable = [
        'token', 'lead_id', 'scheduled_by', 'scheduled_at', 'duration_minutes', 'timezone',
        'attendee_email', 'attendee_name',
        'google_event_id', 'google_meet_link', 'status', 'notes',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Meeting $meeting) {
            if (empty($meeting->token)) {
                $meeting->token = bin2hex(random_bytes(24));
            }
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
