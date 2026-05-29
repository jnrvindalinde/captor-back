<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'weekday', 'start_time', 'end_time',
        'slot_minutes', 'buffer_minutes', 'timezone', 'active',
    ];

    protected $casts = [
        'weekday' => 'integer',
        'slot_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
