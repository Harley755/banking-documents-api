<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration pour la table documents
     * Stocke les métadonnées des documents confidentiels
     * Le contenu chiffré est stocké dans storage/app/private
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Métadonnées du document
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_filename'); // Nom original du fichier
            $table->string('encrypted_filename'); // Nom du fichier chiffré sur disque
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // Taille en octets
            
            // Type de document KYC (Know Your Customer)
            $table->enum('document_type', [
                'passport',
                'id_card',
                'proof_of_address',
                'bank_statement',
                'contract',
                'tax_document',
                'other'
            ]);
            
            // Statut du document
            $table->enum('status', [
                'pending_scan',    // En attente de scan antivirus
                'scanning',        // Scan en cours
                'clean',          // Sain et validé
                'infected',       // Virus détecté
                'failed'          // Échec du scan
            ])->default('pending_scan');
            
            // Sécurité et traçabilité
            $table->string('checksum', 64); // Hash SHA-256 du fichier original
            $table->timestamp('scanned_at')->nullable();
            $table->text('scan_result')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete pour conformité RGPD
            
            // Index pour performances
            $table->index(['user_id', 'status']);
            $table->index('document_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};