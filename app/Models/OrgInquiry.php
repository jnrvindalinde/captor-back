<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrgInquiry extends Model
{
    protected $table = 'org_inquiries';

    protected $fillable = [
        'lead_id', 'about', 'role', 'organization', 'contact_kind', 'contact_value',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
