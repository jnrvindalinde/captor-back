<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = ['lead_id', 'topic', 'message'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
