<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBanjaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $banja = $this->route('banja');

        return $this->user()->hasRole('admin') ||
               ($this->user()->hasRole('spa_manager') && $this->user()->id === $banja->user_id);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $banjaId = $this->route('banja')->id;

        return [
            'naziv' => 'required|string|max:255|unique:banje,naziv,' . $banjaId,
            'slug' => 'nullable|string|max:255|unique:banje,slug,' . $banjaId . '|regex:/^[a-z0-9-]+$/',
            'grad' => 'required|string|max:100',
            'regija' => 'nullable|string|max:100',
            'adresa' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'telefon' => 'nullable|string|max:50|regex:/^[\d\s\+\-\(\)]+$/',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => 'required|string|max:1000',
            'detaljni_opis' => 'nullable|string|max:10000',

            // Medicinski podaci
            'medicinski_nadzor' => 'boolean',
            'fizijatar_prisutan' => 'boolean',

            // Smještaj
            'ima_smjestaj' => 'boolean',
            'broj_kreveta' => 'nullable|integer|min:0|max:1000',

            // Online funkcionalnosti
            'online_rezervacija' => 'boolean',
            'online_upit' => 'boolean',

            // Status (only admin can change)
            'verifikovan' => Rule::when($this->user()->hasRole('admin'), 'boolean'),
            'aktivan' => Rule::when($this->user()->hasRole('admin'), 'boolean'),

            // Taxonomies
            'vrste' => 'required|array|min:1',
            'vrste.*' => 'exists:vrste_banja,id',
            'indikacije' => 'required|array|min:1',
            'indikacije.*' => 'exists:indikacije,id',
            'terapije' => 'required|array|min:1',
            'terapije.*' => 'exists:terapije,id',

            // Galerija
            'featured_slika' => 'nullable|url',
            'galerija' => 'nullable|array|max:20',
            'galerija.*' => 'url',

            // Radno vrijeme
            'radno_vrijeme' => 'nullable|array',
            'radno_vrijeme.*.dan' => 'required_with:radno_vrijeme|string|in:ponedeljak,utorak,sreda,cetvrtak,petak,subota,nedelja',
            'radno_vrijeme.*.od' => 'required_with:radno_vrijeme|date_format:H:i',
            'radno_vrijeme.*.do' => 'required_with:radno_vrijeme|date_format:H:i|after:radno_vrijeme.*.od',
            'radno_vrijeme.*.zatvoreno' => 'boolean',

            // SEO
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'naziv.required' => 'Naziv banje je obavezan',
            'naziv.unique' => 'Banja sa ovim nazivom već postoji',
            'slug.regex' => 'Slug može sadržati samo mala slova, brojeve i crtice',
            'slug.unique' => 'Ovaj slug već postoji',
            'grad.required' => 'Grad je obavezan',
            'adresa.required' => 'Adresa je obavezna',
            'opis.required' => 'Opis je obavezan',
            'opis.max' => 'Opis može imati maksimalno 1000 karaktera',
            'detaljni_opis.max' => 'Detaljni opis može imati maksimalno 10000 karaktera',
            'telefon.regex' => 'Telefon može sadržati samo brojeve, razmake i znakove +, -, (, )',
            'email.email' => 'Email adresa nije validna',
            'website.url' => 'Website mora biti validna URL adresa',
            'latitude.between' => 'Latitude mora biti između -90 i 90',
            'longitude.between' => 'Longitude mora biti između -180 i 180',
            'broj_kreveta.min' => 'Broj kreveta ne može biti negativan',
            'broj_kreveta.max' => 'Broj kreveta ne može biti veći od 1000',
            'vrste.required' => 'Morate odabrati najmanje jednu vrstu banje',
            'vrste.min' => 'Morate odabrati najmanje jednu vrstu banje',
            'vrste.*.exists' => 'Odabrana vrsta banje ne postoji',
            'indikacije.required' => 'Morate odabrati najmanje jednu indikaciju',
            'indikacije.min' => 'Morate odabrati najmanje jednu indikaciju',
            'indikacije.*.exists' => 'Odabrana indikacija ne postoji',
            'terapije.required' => 'Morate odabrati najmanje jednu terapiju',
            'terapije.min' => 'Morate odabrati najmanje jednu terapiju',
            'terapije.*.exists' => 'Odabrana terapija ne postoji',
            'galerija.max' => 'Možete dodati maksimalno 20 slika u galeriju',
            'galerija.*.url' => 'Sve slike u galeriji moraju biti validne URL adrese',
            'featured_slika.url' => 'Featured slika mora biti validna URL adresa',
            'meta_title.max' => 'Meta title može imati maksimalno 255 karaktera',
            'meta_description.max' => 'Meta description može imati maksimalno 500 karaktera',
            'meta_keywords.max' => 'Meta keywords mogu imati maksimalno 500 karaktera',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Auto-generate slug if not provided
        if (!$this->slug && $this->naziv) {
            $this->merge([
                'slug' => \Str::slug($this->naziv)
            ]);
        }

        // Convert boolean strings to actual booleans
        $booleanFields = [
            'medicinski_nadzor', 'fizijatar_prisutan', 'ima_smjestaj',
            'online_rezervacija', 'online_upit', 'verifikovan', 'aktivan'
        ];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)
                ]);
            }
        }
    }
}
