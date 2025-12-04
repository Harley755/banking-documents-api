<?php

namespace App\Enums;

/**
 * Enum des actions auditées
 * Conformité RGPD: traçabilité des accès aux données
 */
enum AuditAction: string
{
    case DOCUMENT_CREATED = 'document.created';
    case DOCUMENT_VIEWED = 'document.viewed';
    case DOCUMENT_DOWNLOADED = 'document.downloaded';
    case DOCUMENT_UPDATED = 'document.updated';
    case DOCUMENT_DELETED = 'document.deleted';
    case DOCUMENT_SHARED = 'document.shared';
    case SHARE_ACCESSED = 'document.share_accessed';
    case SCAN_COMPLETED = 'document.scan_completed';
    case VIRUS_DETECTED = 'document.virus_detected';
    
    /**
     * Description de l'action pour les logs
     */
    public function description(): string
    {
        return match($this) {
            self::DOCUMENT_CREATED => 'Document créé',
            self::DOCUMENT_VIEWED => 'Document consulté',
            self::DOCUMENT_DOWNLOADED => 'Document téléchargé',
            self::DOCUMENT_UPDATED => 'Document mis à jour',
            self::DOCUMENT_DELETED => 'Document supprimé',
            self::DOCUMENT_SHARED => 'Document partagé',
            self::SHARE_ACCESSED => 'Accès via lien de partage',
            self::SCAN_COMPLETED => 'Scan antivirus complété',
            self::VIRUS_DETECTED => 'Virus détecté',
        };
    }
}