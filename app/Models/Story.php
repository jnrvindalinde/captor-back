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

    public const CATEGORIES = ['School', 'Scholarship', 'Job', 'Career'];

    protected $fillable = [
        'slug', 'title', 'summary', 'quote', 'body', 'person_name', 'person_role',
        'outcome', 'outcome_label', 'categories', 'cover_image', 'gallery',
        'status', 'author_id',
    ];

    protected $casts = [
        'categories' => 'array',
        'gallery' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
