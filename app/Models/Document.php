<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'original_filename',
        'encrypted_filename',
        'mime_type',
        'file_size',
        'document_type',
        'status',
        'checksum',
        'scanned_at',
        'scan_result',
    ];

    protected $casts = [
        'status' => DocumentStatus::class,
        'file_size' => 'integer',
        'scanned_at' => 'datetime',
    ];

    protected $hidden = [
        'encrypted_filename', // Ne jamais exposer le nom du fichier chiffré
    ];

    /**
     * Propriétaire du document
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Partages actifs de ce document
     */
    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    /**
     * Logs d'audit pour ce document
     */
    public function audits(): MorphMany
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /**
     * Scope: documents sains uniquement
     */
    public function scopeClean($query)
    {
        return $query->where('status', DocumentStatus::CLEAN);
    }

    /**
     * Scope: documents d'un utilisateur
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Vérifie si le document est téléchargeable
     */
    public function isDownloadable(): bool
    {
        return $this->status === DocumentStatus::CLEAN;
    }

    /**
     * Vérifie si le document est partageable
     */
    public function isShareable(): bool
    {
        return $this->status === DocumentStatus::CLEAN;
    }

    /**
     * Taille formatée pour affichage
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}