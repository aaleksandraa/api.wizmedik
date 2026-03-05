<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PharmacyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enabled = \App\Models\SiteSetting::get('pharmacy_registration_enabled', 'true') === 'true';

        if (!$enabled) {
            abort(403, 'Registracija apoteka trenutno nije dostupna.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            // Firm
            'naziv_brenda' => ['required', 'string', 'max:255'],
            'pravni_naziv' => ['nullable', 'string', 'max:255'],
            'broj_licence' => ['nullable', 'string', 'max:64'],
            'opis' => ['nullable', 'string', 'max:5000'],

            // Public contact
            'email' => ['required', 'email:rfc', 'max:255'],
            'telefon' => ['required', 'string', 'max:64', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'website' => ['nullable', 'url', 'max:255'],

            // Contact person
            'ime' => ['required', 'string', 'max:200'],

            // First branch
            'branch_naziv' => ['nullable', 'string', 'max:255'],
            'adresa' => ['required', 'string', 'max:255'],
            'grad' => ['required', 'string', 'max:100'],
            'postanski_broj' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_maps_link' => ['nullable', 'url', 'max:500'],
            'is_24h' => ['nullable', 'boolean'],
            'kratki_opis' => ['nullable', 'string', 'max:2000'],

            // Account
            'account_email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
            'password_confirmation' => ['required'],

            // Legal
            'prihvatam_uslove' => ['required', 'accepted'],

            // Optional
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'naziv_brenda.required' => 'Naziv apoteke je obavezan.',
            'email.required' => 'Email adresa je obavezna.',
            'email.email' => 'Email adresa nije validna.',
            'telefon.required' => 'Telefon je obavezan.',
            'telefon.regex' => 'Telefon nije u validnom formatu.',
            'adresa.required' => 'Adresa poslovnice je obavezna.',
            'grad.required' => 'Grad je obavezan.',
            'ime.required' => 'Ime kontakt osobe je obavezno.',
            'account_email.required' => 'Email za prijavu je obavezan.',
            'account_email.email' => 'Email za prijavu nije validan.',
            'account_email.unique' => 'Email za prijavu je vec registrovan.',
            'password.required' => 'Lozinka je obavezna.',
            'password.confirmed' => 'Lozinke se ne poklapaju.',
            'password.uncompromised' => 'Lozinka je kompromitovana. Molimo izaberite sigurniju lozinku.',
            'prihvatam_uslove.accepted' => 'Morate prihvatiti uslove koristenja.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->website && !preg_match('/^https?:\/\//i', $this->website)) {
            $this->merge(['website' => 'https://' . $this->website]);
        }
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Greske u validaciji. Molimo provjerite unesene podatke.',
                'errors' => $validator->errors()->toArray(),
            ], 422)
        );
    }
}
