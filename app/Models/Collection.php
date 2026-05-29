<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'description', 'schema'];

    protected $casts = [
        'schema' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(CollectionItem::class)->orderBy('position');
    }

    public function publishedItems(): HasMany
    {
        return $this->items()->where('status', 'published');
    }
}
