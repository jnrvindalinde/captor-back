<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NavigationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'menu_id', 'label_en', 'label_fr',
        'href', 'target', 'sort_order', 'visible',
    ];

    protected $casts = [
        'visible'    => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (NavigationItem $i) {
            $i->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'menu_id');
    }
}
