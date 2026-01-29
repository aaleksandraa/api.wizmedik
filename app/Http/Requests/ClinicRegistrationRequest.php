<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ClinicRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if clinic registration is enabled
        $enabled = \App\Models\SiteSetting::get('clinic_registration_enabled', 'true') === 'true';

        if (!$enabled) {
            abort(403, 'Registracija klinika trenutno nije dostupna.');
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Clinic info
            'naziv' => [
                'required',
                'string',
                'min:3',
                'max:200',
                'unique:klinike,naziv',
            ],

            // Contact person
            'ime' => [
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
                'unique:klinike,email',
                function ($attribute, $value, $fail) {
                    // Check if email exists in registration_requests with status other than 'rejected'
                    $exists = \App\Models\RegistrationRequest::where('email', $value)
                        ->where('status', '!=', 'rejected')
                        ->exists();

                    if ($exists) {
                        $fail('Ova email adresa je već registrovana.');
                    }
                },
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

            // Location
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

            // Optional info
            'website' => [
                'nullable',
                'url',
                'max:255',
            ],
            'message' => [
                'nullable',
                'string',
                'max:1000',
            ],

            // Documents (if required)
            'documents' => [
                'nullable',
                'array',
                'max:5',
            ],
            'documents.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120', // 5MB
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
            'naziv.required' => 'Naziv klinike je obavezan.',
            'naziv.unique' => 'Klinika sa ovim nazivom već postoji.',
            'ime.required' => 'Ime kontakt osobe je obavezno.',
            'ime.regex' => 'Ime može sadržati samo slova.',
            'email.required' => 'Email adresa je obavezna.',
            'email.email' => 'Email adresa nije validna.',
            'email.unique' => 'Ova email adresa je već registrovana.',
            'telefon.required' => 'Broj telefona je obavezan.',
            'telefon.regex' => 'Broj telefona nije u validnom formatu.',
            'password.required' => 'Lozinka je obavezna.',
            'password.confirmed' => 'Lozinke se ne poklapaju.',
            'password.uncompromised' => 'Ova lozinka je pronađena u poznatim sigurnosnim probojima. Molimo koristite drugu, sigurniju lozinku.',
            'adresa.required' => 'Adresa je obavezna.',
            'grad.required' => 'Grad je obavezan.',
            'website.url' => 'Website mora biti validna URL adresa.',
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
            'naziv' => strip_tags($this->naziv),
            'ime' => strip_tags($this->ime),
            'email' => strtolower(trim($this->email)),
            'telefon' => preg_replace('/[^0-9+\-\s()]/', '', $this->telefon),
            'adresa' => strip_tags($this->adresa),
            'grad' => strip_tags($this->grad),
            'message' => strip_tags($this->message),
        ]);

        // Auto-add https:// to website if missing protocol
        if ($this->website && !preg_match('/^https?:\/\//i', $this->website)) {
            $this->merge([
                'website' => 'https://' . $this->website,
            ]);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Greške u validaciji. Molimo provjerite unesene podatke.',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
