<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Story extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    public const OUTCOMES = [
        'admission', 'scholarship', 'placement', 'transition', 'achievement',
    ];

    protected $fillable = [
        'slug', 'title', 'summary', 'body', 'person_name', 'person_role',
        'outcome', 'cover_image', 'status', 'author_id',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
