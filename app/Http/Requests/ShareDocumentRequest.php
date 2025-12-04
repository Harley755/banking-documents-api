<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la création d'un partage
 */
class ShareDocumentRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête
     */
    public function authorize(): bool
    {
        return true; // Géré par la Policy
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'expires_in_hours' => [
                'nullable',
                'integer',
                'min:1',
                'max:168', // Max 7 jours
            ],
            'max_downloads' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'shared_with_email' => [
                'nullable',
                'email',
                'max:255',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'expires_in_hours.integer' => 'La durée doit être un nombre entier',
            'expires_in_hours.min' => 'La durée minimale est de 1 heure',
            'expires_in_hours.max' => 'La durée maximale est de 168 heures (7 jours)',
            'max_downloads.integer' => 'Le nombre de téléchargements doit être un entier',
            'max_downloads.min' => 'Minimum 1 téléchargement',
            'max_downloads.max' => 'Maximum 100 téléchargements',
            'shared_with_email.email' => 'Email invalide',
        ];
    }

    /**
     * Valeurs par défaut
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'expires_in_hours' => $this->expires_in_hours ?? 24,
            'max_downloads' => $this->max_downloads ?? 1,
        ]);
    }
}