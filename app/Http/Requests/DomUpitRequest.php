<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DomUpitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'ime' => 'required|string|max:100|regex:/^[a-zA-ZšđčćžŠĐČĆŽ\s]+$/',
            'email' => 'required|email|max:255',
            'telefon' => 'nullable|string|max:50|regex:/^[\d\s\+\-\(\)]+$/',
            'poruka' => 'required|string|min:10|max:2000',
            'opis_potreba' => 'nullable|string|max:2000',
            'zelja_posjeta' => 'boolean',
            'tip' => 'required|in:upit,rezervacija',
            'gdpr_saglasnost' => 'required|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ime.required' => 'Ime je obavezno',
            'ime.regex' => 'Ime može sadržati samo slova i razmake',
            'ime.max' => 'Ime može imati maksimalno 100 karaktera',
            'email.required' => 'Email adresa je obavezna',
            'email.email' => 'Email adresa nije validna',
            'email.max' => 'Email adresa može imati maksimalno 255 karaktera',
            'telefon.regex' => 'Telefon može sadržati samo brojeve, razmake i znakove +, -, (, )',
            'telefon.max' => 'Telefon može imati maksimalno 50 karaktera',
            'poruka.required' => 'Poruka je obavezna',
            'poruka.min' => 'Poruka mora imati najmanje 10 karaktera',
            'poruka.max' => 'Poruka može imati maksimalno 2000 karaktera',
            'opis_potreba.max' => 'Opis potreba može imati maksimalno 2000 karaktera',
            'tip.required' => 'Tip upita je obavezan',
            'tip.in' => 'Tip upita mora biti upit ili rezervacija',
            'gdpr_saglasnost.required' => 'Morate prihvatiti uslove korišćenja',
            'gdpr_saglasnost.accepted' => 'Morate prihvatiti uslove korišćenja',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize input
        if ($this->ime) {
            $this->merge([
                'ime' => trim($this->ime)
            ]);
        }

        if ($this->email) {
            $this->merge([
                'email' => strtolower(trim($this->email))
            ]);
        }

        if ($this->poruka) {
            $this->merge([
                'poruka' => trim($this->poruka)
            ]);
        }

        if ($this->opis_potreba) {
            $this->merge([
                'opis_potreba' => trim($this->opis_potreba)
            ]);
        }

        // Set user_id if authenticated
        if (auth()->check()) {
            $this->merge([
                'user_id' => auth()->id()
            ]);
        }

        // Set IP address
        $this->merge([
            'ip_adresa' => request()->ip()
        ]);

        // Convert boolean
        $this->merge([
            'zelja_posjeta' => filter_var($this->zelja_posjeta, FILTER_VALIDATE_BOOLEAN)
        ]);
    }
}
