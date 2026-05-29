<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageAudit extends Model
{
    public $timestamps = false;

    protected $fillable = ['page_id', 'user_id', 'action', 'payload', 'created_at'];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
