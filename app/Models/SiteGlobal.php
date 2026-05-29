<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteGlobal extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name', 'tagline_en', 'tagline_fr',
        'logo_light_url', 'logo_dark_url',
        'contact_email', 'contact_phone',
        'address_en', 'address_fr',
        'socials',
        'footer_copyright_en', 'footer_copyright_fr',
    ];

    protected $casts = [
        'socials' => 'array',
    ];

    /**
     * Singleton: return (or create) the single globals row.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'company_name' => 'Career360 Consult',
        ]);
    }
}
