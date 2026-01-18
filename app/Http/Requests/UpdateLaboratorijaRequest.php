<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLaboratorijaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $laboratorijaId = $this->route('id') ?? auth()->user()->laboratorija->id;

        return [
            // Basic information
            'naziv' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('laboratorije')->ignore($laboratorijaId)],
            'opis' => ['nullable', 'string', 'max:5000'],
            'kratak_opis' => ['nullable', 'string', 'max:500'],

            // Contact information
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('laboratorije')->ignore($laboratorijaId)],
            'telefon' => ['sometimes', 'required', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'telefon_2' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s\+\-\(\)]+$/'],
            'website' => ['nullable', 'url', 'max:255'],

            // Location
            'adresa' => ['sometimes', 'required', 'string', 'max:255'],
            'grad' => ['sometimes', 'required', 'string', 'max:100'],
            'postanski_broj' => ['nullable', 'string', 'max:10'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'google_maps_link' => ['nullable', 'url', 'max:500'],

            // Images
            'featured_slika' => ['nullable', 'string', 'max:500'],
            'profilna_slika' => ['nullable', 'string', 'max:500'],

            // Features
            'online_rezultati' => ['nullable', 'boolean'],
            'prosjecno_vrijeme_rezultata' => ['nullable', 'string', 'max:100'],
            'napomena' => ['nullable', 'string', 'max:2000'],

            // SEO
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['string', 'max:100'],

            // Relations
            'klinika_id' => ['nullable', 'exists:klinike,id'],
            'doktor_id' => ['nullable', 'exists:doktori,id'],
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
            'email.required' => 'Email adresa je obavezna.',
            'email.email' => 'Email adresa nije validna.',
            'email.unique' => 'Ova email adresa je već registrovana.',
            'telefon.required' => 'Broj telefona je obavezan.',
            'telefon.regex' => 'Broj telefona nije u validnom formatu.',
            'adresa.required' => 'Adresa je obavezna.',
            'grad.required' => 'Grad je obavezan.',
        ];
    }
}
