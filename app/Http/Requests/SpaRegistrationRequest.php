<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SpaRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Basic information
            'naziv' => ['required', 'string', 'max:255', 'unique:banje,naziv'],
            'opis' => ['nullable', 'string', 'max:5000'],

            // Contact information (public)
            'email' => ['required', 'email:rfc', 'max:255', 'unique:banje,email'],
            'telefon' => ['required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'website' => ['nullable', 'url', 'max:255'],

            // Location
            'adresa' => ['required', 'string', 'max:255'],
            'grad' => ['required', 'string', 'max:100'],
            'regija' => ['nullable', 'string', 'max:100'],

            // Spa specific
            'vrste' => ['nullable', 'array'],
            'vrste.*' => ['integer', 'exists:vrste_banja,id'],
            'medicinski_nadzor' => ['nullable', 'boolean'],
            'ima_smjestaj' => ['nullable', 'boolean'],

            // Contact person
            'kontakt_ime' => ['required', 'string', 'max:100'],
            'kontakt_prezime' => ['required', 'string', 'max:100'],

            // Authentication (account email - for login)
            'account_email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
            'password_confirmation' => ['required'],

            // Terms acceptance
            'prihvatam_uslove' => ['required', 'accepted'],

            // Optional message/notes
            'napomena' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'naziv.required' => 'Naziv banje je obavezan.',
            'naziv.unique' => 'Banja sa ovim nazivom već postoji.',
            'email.required' => 'Email adresa za javnost je obavezna.',
            'email.email' => 'Email adresa nije validna.',
            'email.unique' => 'Ova email adresa je već registrovana.',
            'telefon.required' => 'Broj telefona je obavezan.',
            'telefon.regex' => 'Broj telefona nije u validnom formatu.',
            'website.url' => 'Website mora biti validna URL adresa (npr. wizmedik.com).',
            'adresa.required' => 'Adresa je obavezna.',
            'grad.required' => 'Grad je obavezan.',
            'kontakt_ime.required' => 'Ime kontakt osobe je obavezno.',
            'kontakt_prezime.required' => 'Prezime kontakt osobe je obavezno.',
            'account_email.required' => 'Email za prijavu je obavezan.',
            'account_email.email' => 'Email za prijavu nije validan.',
            'account_email.unique' => 'Ovaj email je već registrovan kao korisnički nalog.',
            'password.required' => 'Lozinka je obavezna.',
            'password.min' => 'Lozinka mora imati najmanje 12 karaktera.',
            'password.confirmed' => 'Lozinke se ne poklapaju.',
            'password.uncompromised' => 'Ova lozinka je pronađena u poznatim sigurnosnim probojima. Molimo koristite drugu, sigurniju lozinku.',
            'prihvatam_uslove.required' => 'Morate prihvatiti uslove korištenja.',
            'prihvatam_uslove.accepted' => 'Morate prihvatiti uslove korištenja.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'naziv' => 'naziv banje',
            'email' => 'email adresa za javnost',
            'account_email' => 'email za prijavu',
            'telefon' => 'broj telefona',
            'adresa' => 'adresa',
            'grad' => 'grad',
            'kontakt_ime' => 'ime kontakt osobe',
            'kontakt_prezime' => 'prezime kontakt osobe',
            'password' => 'lozinka',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-add https:// to website if missing protocol
        if ($this->website && !preg_match('/^https?:\/\//i', $this->website)) {
            $this->merge([
                'website' => 'https://' . $this->website,
            ]);
        }
    }
}
