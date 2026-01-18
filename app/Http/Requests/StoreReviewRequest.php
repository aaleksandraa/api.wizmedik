<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'termin_id' => ['required', 'integer', 'exists:termini,id'],
            'recenziran_type' => ['required', 'string', 'in:App\\Models\\Doktor,App\\Models\\Klinika'],
            'recenziran_id' => ['required', 'integer'],
            'ocjena' => ['required', 'integer', 'min:1', 'max:5'],
            'komentar' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'ocjena.required' => 'Ocjena je obavezna.',
            'ocjena.min' => 'Minimalna ocjena je 1.',
            'ocjena.max' => 'Maksimalna ocjena je 5.',
            'komentar.max' => 'Komentar može imati maksimalno 1000 karaktera.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize comment - remove all HTML tags
        if ($this->has('komentar')) {
            $this->merge([
                'komentar' => strip_tags($this->komentar)
            ]);
        }
    }
}
