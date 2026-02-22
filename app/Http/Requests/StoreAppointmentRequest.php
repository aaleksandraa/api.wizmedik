<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doktor_id' => ['required', 'integer', 'exists:doktori,id'],
            'datum_vrijeme' => [
                'required',
                'date',
                'after:now',
                'before:' . now()->addMonths(6)->toDateString()
            ],
            'razlog' => ['nullable', 'string', 'max:500'],
            'napomene' => ['nullable', 'string', 'max:1000'],
            'usluga_id' => ['nullable', 'integer', 'exists:usluge,id'],
            'trajanje_minuti' => ['nullable', 'integer', 'min:15', 'max:240'],

            // Guest booking fields
            'guest_ime' => ['required_without:user_id', 'string', 'max:100', 'regex:/^[\p{L}\s\-]+$/u'],
            'guest_prezime' => ['required_without:user_id', 'string', 'max:100', 'regex:/^[\p{L}\s\-]+$/u'],
            'guest_telefon' => ['required_without:user_id', 'string', 'regex:/^[\+]?[0-9\s\-\(\)]{9,20}$/'],
            'guest_email' => ['nullable', 'email:rfc,dns', 'max:255'],

            'gostovanje_id' => ['nullable', 'integer', 'exists:klinika_doktor_gostovanja,id'],
            'klinika_id' => ['nullable', 'integer', 'exists:klinike,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'doktor_id.required' => 'Doktor je obavezan.',
            'doktor_id.exists' => 'Odabrani doktor ne postoji.',
            'datum_vrijeme.required' => 'Datum i vrijeme su obavezni.',
            'datum_vrijeme.after' => 'Ne možete zakazati termin u prošlosti.',
            'datum_vrijeme.before' => 'Možete zakazati termin maksimalno 6 mjeseci unaprijed.',
            'guest_ime.required_without' => 'Ime je obavezno.',
            'guest_ime.regex' => 'Ime može sadržati samo slova, razmake i crtice.',
            'guest_prezime.required_without' => 'Prezime je obavezno.',
            'guest_prezime.regex' => 'Prezime može sadržati samo slova, razmake i crtice.',
            'guest_telefon.required_without' => 'Telefon je obavezan.',
            'guest_telefon.regex' => 'Telefon nije u validnom formatu.',
            'guest_email.email' => 'Email nije validan.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Backward compatibility for payloads that still use ime/prezime/telefon/email
        $this->merge([
            'guest_ime' => $this->input('guest_ime', $this->input('ime')),
            'guest_prezime' => $this->input('guest_prezime', $this->input('prezime')),
            'guest_telefon' => $this->input('guest_telefon', $this->input('telefon')),
            'guest_email' => $this->input('guest_email', $this->input('email')),
            // Support both napomena and napomene keys
            'napomene' => $this->input('napomene', $this->input('napomena')),
        ]);

        // Sanitize input
        if ($this->has('razlog')) {
            $this->merge([
                'razlog' => strip_tags($this->razlog)
            ]);
        }

        if ($this->has('napomene')) {
            $this->merge([
                'napomene' => strip_tags($this->napomene)
            ]);
        }
    }
}
