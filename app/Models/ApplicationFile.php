<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationFile extends Model
{
    protected $fillable = ['application_id', 'original_name', 'mime', 'size', 'path'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
