<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('doctor');
    }

    public function rules(): array
    {
        return [
            'ime' => ['sometimes', 'string', 'max:100', 'regex:/^[\p{L}\s\-]+$/u'],
            'prezime' => ['sometimes', 'string', 'max:100', 'regex:/^[\p{L}\s\-]+$/u'],
            'telefon' => ['sometimes', 'string', 'regex:/^[\+]?[0-9\s\-\(\)]{9,20}$/'],
            'email' => ['sometimes', 'email:rfc,dns', 'max:255', 'unique:doktori,email,' . $this->route('id')],
            'opis' => ['nullable', 'string', 'max:2000'],
            'grad' => ['sometimes', 'string', 'max:100'],
            'lokacija' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'prihvata_online' => ['sometimes', 'boolean'],
            'slot_trajanje_minuti' => ['sometimes', 'integer', 'min:15', 'max:120'],
            'auto_potvrda' => ['sometimes', 'boolean'],
            'radno_vrijeme' => ['nullable', 'json'],
            'pauze' => ['nullable', 'json'],
            'odmori' => ['nullable', 'json'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize HTML from description
        if ($this->has('opis')) {
            $this->merge([
                'opis' => strip_tags($this->opis, '<p><br><strong><em><ul><ol><li>')
            ]);
        }
    }
}
