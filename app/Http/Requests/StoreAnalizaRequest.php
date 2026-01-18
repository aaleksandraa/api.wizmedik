<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnalizaRequest extends FormRequest
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
        $rules = [
            'kategorija_id' => ['required', 'exists:kategorije_analiza,id'],
            'naziv' => ['required', 'string', 'max:255'],
            'kod' => ['nullable', 'string', 'max:50'],
            'opis' => ['nullable', 'string', 'max:5000'],
            'kratak_opis' => ['nullable', 'string', 'max:500'],
            'cijena' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'akcijska_cijena' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'lt:cijena'],
            'akcija_od' => ['nullable', 'date', 'before_or_equal:akcija_do'],
            'akcija_do' => ['nullable', 'date', 'after_or_equal:akcija_od'],
            'prosjecno_vrijeme_rezultata' => ['nullable', 'string', 'max:100'],
            'priprema' => ['nullable', 'string', 'max:2000'],
            'napomena' => ['nullable', 'string', 'max:1000'],
            'kljucne_rijeci' => ['nullable', 'array'],
            'kljucne_rijeci.*' => ['string', 'max:100'],
            'sinonimi' => ['nullable', 'array'],
            'sinonimi.*' => ['string', 'max:100'],
            'hitno_dostupno' => ['nullable', 'boolean'],
            'kucna_posjeta' => ['nullable', 'boolean'],
            'online_rezultati' => ['nullable', 'boolean'],
            'aktivan' => ['nullable', 'boolean'],
            'redoslijed' => ['nullable', 'integer', 'min:0'],
        ];

        // For updates, make slug unique except for current record
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $analizaId = $this->route('id');
            $rules['slug'] = ['nullable', 'string', 'max:255', 'unique:analize,slug,' . $analizaId . ',id,laboratorija_id,' . auth()->user()->laboratorija->id];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'kategorija_id.required' => 'Kategorija je obavezna.',
            'kategorija_id.exists' => 'Odabrana kategorija ne postoji.',
            'naziv.required' => 'Naziv analize je obavezan.',
            'cijena.required' => 'Cijena je obavezna.',
            'cijena.numeric' => 'Cijena mora biti broj.',
            'cijena.min' => 'Cijena ne može biti negativna.',
            'akcijska_cijena.lt' => 'Akcijska cijena mora biti manja od regularne cijene.',
            'akcija_od.before_or_equal' => 'Datum početka akcije mora biti prije ili jednak datumu kraja.',
            'akcija_do.after_or_equal' => 'Datum kraja akcije mora biti nakon ili jednak datumu početka.',
        ];
    }
}
