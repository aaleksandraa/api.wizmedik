<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class DoctorRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if doctor registration is enabled
        $enabled = \App\Models\SiteSetting::get('doctor_registration_enabled', 'true') === 'true';

        if (!$enabled) {
            abort(403, 'Registracija doktora trenutno nije dostupna.');
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Personal info
            'ime' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}\s\-\']+$/u', // Only letters, spaces, hyphens, apostrophes
            ],
            'prezime' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[\p{L}\s\-\']+$/u',
            ],

            // Contact
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                'unique:users,email',
                'unique:doktori,email',
                'unique:registration_requests,email',
            ],
            'telefon' => [
                'required',
                'string',
                'regex:/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{3,6}$/',
            ],

            // Password
            'password' => [
                'required',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],

            // Professional info
            'specialty_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'specialty_ids.*' => [
                'required',
                'integer',
                'exists:specijalnosti,id',
            ],
            // Legacy support for old field name
            'specijalnost_id' => [
                'nullable',
                'exists:specijalnosti,id',
            ],
            'adresa' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
            'grad' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],

            // Optional message
            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // Documents (if required)
            'documents' => [
                'nullable',
                'array',
                'max:5', // Maximum 5 documents
            ],
            'documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5MB max per file
            ],

            // Terms acceptance
            'terms_accepted' => [
                'required',
                'accepted',
            ],
            'privacy_accepted' => [
                'required',
                'accepted',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ime.required' => 'Ime je obavezno.',
            'ime.regex' => 'Ime može sadržati samo slova.',
            'prezime.required' => 'Prezime je obavezno.',
            'prezime.regex' => 'Prezime može sadržati samo slova.',
            'email.required' => 'Email adresa je obavezna.',
            'email.email' => 'Email adresa nije validna.',
            'email.unique' => 'Ova email adresa je već registrovana.',
            'telefon.required' => 'Broj telefona je obavezan.',
            'telefon.regex' => 'Broj telefona nije u validnom formatu.',
            'password.required' => 'Lozinka je obavezna.',
            'password.confirmed' => 'Lozinke se ne poklapaju.',
            'password.uncompromised' => 'Ova lozinka je pronađena u poznatim sigurnosnim probojima. Molimo koristite drugu, sigurniju lozinku.',
            'specialty_ids.required' => 'Morate odabrati najmanje jednu specijalnost.',
            'specialty_ids.*.exists' => 'Odabrana specijalnost ne postoji.',
            'specijalnost_id.required' => 'Specijalnost je obavezna.',
            'specijalnost_id.exists' => 'Odabrana specijalnost ne postoji.',
            'adresa.required' => 'Adresa je obavezna.',
            'grad.required' => 'Grad je obavezan.',
            'documents.*.mimes' => 'Dokumenti moraju biti PDF, JPG ili PNG format.',
            'documents.*.max' => 'Dokument ne smije biti veći od 5MB.',
            'terms_accepted.accepted' => 'Morate prihvatiti uslove korištenja.',
            'privacy_accepted.accepted' => 'Morate prihvatiti politiku privatnosti.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize inputs
        $this->merge([
            'ime' => strip_tags($this->ime),
            'prezime' => strip_tags($this->prezime),
            'email' => strtolower(trim($this->email)),
            'telefon' => preg_replace('/[^0-9+\-\s()]/', '', $this->telefon),
            'adresa' => strip_tags($this->adresa),
            'grad' => strip_tags($this->grad),
            'message' => strip_tags($this->message),
        ]);
    }
}
