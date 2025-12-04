<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Validation de l'upload de document
 */
class StoreDocumentRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête
     */
    public function authorize(): bool
    {
        return true; // Géré par auth:sanctum
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10 MB max
                'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', // Types autorisés
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'document_type' => [
                'required',
                'in:passport,id_card,proof_of_address,bank_statement,contract,tax_document,other',
            ],
        ];
    }

    /**
     * Messages d'erreur personnalisés
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Le fichier est obligatoire',
            'file.max' => 'Le fichier ne doit pas dépasser 10 MB',
            'file.mimes' => 'Type de fichier non autorisé',
            'title.required' => 'Le titre est obligatoire',
            'document_type.required' => 'Le type de document est obligatoire',
            'document_type.in' => 'Type de document invalide',
        ];
    }


    /**
     * Force JSON response on validation failure for API endpoints.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        $response = response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422);

        throw new HttpResponseException($response);
    }
}