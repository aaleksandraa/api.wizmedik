<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class LaboratoryRegistrationRequest extends FormRequest
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
            'naziv' => ['required', 'string', 'max:255', 'unique:laboratorije,naziv'],
            'opis' => ['nullable', 'string', 'max:5000'],
            'kratak_opis' => ['nullable', 'string', 'max:500'],

            // Contact information (public)
            'email' => ['required', 'email:rfc', 'max:255', 'unique:laboratorije,email'],
            'telefon' => ['required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'telefon_2' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'website' => ['nullable', 'url', 'max:255'],

            // Location
            'adresa' => ['required', 'string', 'max:255'],
            'grad' => ['required', 'string', 'max:100'],
            'postanski_broj' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_maps_link' => ['nullable', 'url', 'max:500'],

            // Features
            'online_rezultati' => ['nullable', 'boolean'],
            'prosjecno_vrijeme_rezultata' => ['nullable', 'string', 'max:100'],

            // Contact person
            'ime' => ['required', 'string', 'max:200'], // Contact person name

            // Authentication (account email - for login)
            'account_email' => ['nullable', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
            'password_confirmation' => ['required'],

            // Terms acceptance
            'prihvatam_uslove' => ['nullable', 'accepted'],

            // Additional message
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'naziv.required' => 'Naziv laboratorije je obavezan.',
            'naziv.unique' => 'Laboratorija sa ovim nazivom već postoji.',
            'email.required' => 'Email adresa za javnost je obavezna.',
            'email.email' => 'Email adresa nije validna.',
            'email.unique' => 'Ova email adresa je već registrovana.',
            'telefon.required' => 'Broj telefona je obavezan.',
            'telefon.regex' => 'Broj telefona nije u validnom formatu.',
            'adresa.required' => 'Adresa je obavezna.',
            'grad.required' => 'Grad je obavezan.',
            'ime.required' => 'Ime kontakt osobe je obavezno.',
            'account_email.email' => 'Email za prijavu nije validan.',
            'account_email.unique' => 'Ovaj email je već registrovan kao korisnički nalog.',
            'password.required' => 'Lozinka je obavezna.',
            'password.min' => 'Lozinka mora imati najmanje 12 karaktera.',
            'password.confirmed' => 'Lozinke se ne poklapaju.',
            'password.uncompromised' => 'Ova lozinka je pronađena u poznatim sigurnosnim probojima. Molimo koristite drugu, sigurniju lozinku.',
            'prihvatam_uslove.accepted' => 'Morate prihvatiti uslove korištenja.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'naziv' => 'naziv laboratorije',
            'email' => 'email adresa za javnost',
            'account_email' => 'email za prijavu',
            'telefon' => 'broj telefona',
            'adresa' => 'adresa',
            'grad' => 'grad',
            'ime' => 'ime kontakt osobe',
            'password' => 'lozinka',
        ];
    }
}
