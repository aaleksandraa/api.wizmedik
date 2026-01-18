<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DomRecenzijaRequest extends FormRequest
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
            'ime' => 'required_without:user_id|string|max:100|regex:/^[a-zA-ZšđčćžŠĐČĆŽ\s]+$/',
            'ocjena' => 'required|integer|min:1|max:5',
            'komentar' => 'required|string|min:10|max:1000',
            'gdpr_saglasnost' => 'required|accepted',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ime.required_without' => 'Ime je obavezno ako niste prijavljeni',
            'ime.regex' => 'Ime može sadržati samo slova i razmake',
            'ime.max' => 'Ime može imati maksimalno 100 karaktera',
            'ocjena.required' => 'Ocjena je obavezna',
            'ocjena.integer' => 'Ocjena mora biti broj',
            'ocjena.min' => 'Ocjena mora biti najmanje 1',
            'ocjena.max' => 'Ocjena može biti maksimalno 5',
            'komentar.required' => 'Komentar je obavezan',
            'komentar.min' => 'Komentar mora imati najmanje 10 karaktera',
            'komentar.max' => 'Komentar može imati maksimalno 1000 karaktera',
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

        if ($this->komentar) {
            $this->merge([
                'komentar' => trim(strip_tags($this->komentar))
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

        // Set default status
        $this->merge([
            'odobreno' => false,
            'verifikovano' => auth()->check()
        ]);
    }
}
