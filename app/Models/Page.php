<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'slug', 'kind', 'status',
        'title_en', 'title_fr',
        'seo_title_en', 'seo_title_fr',
        'seo_description_en', 'seo_description_fr',
        'og_image_id', 'published_at', 'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected static function booted(): void
    {
        static::creating(function (Page $p) {
            $p->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('position');
    }

    public function publishedSections(): HasMany
    {
        return $this->sections()->where('status', 'published');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_image_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(PageAudit::class);
    }

    /**
     * Deterministic, app-secret-derived preview token. Anyone with the slug
     * + this token can view the draft via the public preview endpoint.
     */
    public function previewToken(): string
    {
        return substr(hash_hmac('sha256', 'page:'.$this->uuid, (string) config('app.key')), 0, 24);
    }
}
