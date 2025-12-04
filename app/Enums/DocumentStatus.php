<?php

namespace App\Enums;

/**
 * Enum des statuts de document
 * Cycle de vie d'un document dans le système
 */
enum DocumentStatus: string
{
    case PENDING_SCAN = 'pending_scan';
    case SCANNING = 'scanning';
    case CLEAN = 'clean';
    case INFECTED = 'infected';
    case FAILED = 'failed';
    
    /**
     * Vérifie si le document peut être téléchargé
     */
    public function isDownloadable(): bool
    {
        return $this === self::CLEAN;
    }
    
    /**
     * Vérifie si le document peut être partagé
     */
    public function isShareable(): bool
    {
        return $this === self::CLEAN;
    }
    
    /**
     * Label lisible pour les humains
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING_SCAN => 'En attente de scan',
            self::SCANNING => 'Analyse en cours',
            self::CLEAN => 'Validé',
            self::INFECTED => 'Virus détecté',
            self::FAILED => 'Échec de l\'analyse',
        };
    }
}