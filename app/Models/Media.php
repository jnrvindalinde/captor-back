<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'provider',
        'public_id',
        'secure_url',
        'format',
        'width',
        'height',
        'bytes',
        'original_filename',
        'folder',
        'alt_en',
        'alt_fr',
        'caption_en',
        'caption_fr',
        'meta',
        'uploaded_by',
    ];

    protected $casts = [
        'meta'   => 'array',
        'width'  => 'integer',
        'height' => 'integer',
        'bytes'  => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Media $m) {
            $m->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
