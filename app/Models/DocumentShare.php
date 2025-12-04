<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'token',
        'shared_with_email',
        'expires_at',
        'max_downloads',
        'download_count',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'max_downloads' => 'integer',
        'download_count' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Le document associé
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
