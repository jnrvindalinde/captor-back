<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resource extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const FORMATS = ['guide', 'document', 'video', 'audio', 'external'];

    protected $fillable = [
        'slug', 'title', 'description', 'format', 'file_path', 'external_url',
        'cover_image', 'status', 'tags', 'author_id',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
