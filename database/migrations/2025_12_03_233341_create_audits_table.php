<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration pour la table audits
     * Journalisation RGPD de toutes les actions sur les documents
     * IMPORTANT: Ne jamais logger le contenu des documents
     */
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            
            // Acteur de l'action
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_email')->nullable(); // Conservation de l'email même si user supprimé
            
            // Ressource concernée
            $table->morphs('auditable'); // Polymorphique: documents, shares, etc.
            
            // Action effectuée
            $table->enum('action', [
                'document.created',
                'document.viewed',
                'document.downloaded',
                'document.updated',
                'document.deleted',
                'document.shared',
                'document.share_accessed',
                'document.scan_completed',
                'document.virus_detected'
            ]);
            
            // Contexte de l'action (sans données sensibles)
            $table->json('metadata')->nullable(); // Ex: {file_size, mime_type}
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            // Résultat de l'action
            $table->enum('result', ['success', 'failure'])->default('success');
            $table->text('error_message')->nullable();
            
            $table->timestamp('created_at')->useCurrent();
            
            // Index pour requêtes de conformité RGPD
            $table->index('user_id');
            $table->index('action');
            // Le morphs() créée déjà un index sur auditable_type/auditable_id
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};