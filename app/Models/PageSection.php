<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PageSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'page_id', 'type', 'position', 'status', 'data',
    ];

    protected $casts = [
        'data'     => 'array',
        'position' => 'integer',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected static function booted(): void
    {
        static::creating(function (PageSection $s) {
            $s->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
