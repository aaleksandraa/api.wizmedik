<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        $dom = $this->route('dom');
        if (!$dom) {
            $dom = \App\Models\Dom::where('user_id', $user->id)->first();
        }

        if (!$dom) {
            return false;
        }

        return ($user->hasRole('dom_manager') || $user->hasRole('care_home_manager') || $user->hasRole('care_home'))
            && $user->id === $dom->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $domId = $this->route('dom')?->id
            ?? \App\Models\Dom::where('user_id', $this->user()->id)->value('id');

        return [
            'naziv' => 'sometimes|required|string|max:255|unique:domovi_njega,naziv,' . $domId,
            'slug' => 'nullable|string|max:255|unique:domovi_njega,slug,' . $domId . '|regex:/^[a-z0-9-]+$/',
            'grad' => 'sometimes|required|string|max:100',
            'regija' => 'nullable|string|max:100',
            'adresa' => 'sometimes|required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_link' => 'nullable|string|max:500',
            'telefon' => 'nullable|string|max:50|regex:/^[\d\s\+\-\(\)]+$/',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'opis' => 'sometimes|required|string|max:1000',
            'detaljni_opis' => 'nullable|string|max:10000',

            // Tip i nivo
            'tip_doma_id' => 'sometimes|required|exists:tipovi_domova,id',
            'nivo_njege_id' => 'sometimes|required|exists:nivoi_njege,id',

            // Admission
            'accepts_tags' => 'nullable|array',
            'not_accepts_text' => 'nullable|string|max:1000',

            // Osoblje
            'nurses_availability' => 'sometimes|required|in:24_7,shifts,on_demand',
            'doctor_availability' => 'sometimes|required|in:permanent,periodic,on_call',
            'has_physiotherapist' => 'boolean',
            'has_physiatrist' => 'boolean',

            // Sigurnost
            'emergency_protocol' => 'boolean',
            'emergency_protocol_text' => 'nullable|string|max:1000',
            'controlled_entry' => 'boolean',
            'video_surveillance' => 'boolean',
            'visiting_rules' => 'nullable|string|max:2000',

            // Cijene
            'pricing_mode' => 'sometimes|required|in:public,on_request',
            'price_from' => 'nullable|numeric|min:0',
            'price_includes' => 'nullable|string|max:1000',
            'extra_charges' => 'nullable|string|max:1000',

            // Online funkcionalnosti
            'online_upit' => 'boolean',

            // Status (only admin can change)
            'verifikovan' => Rule::when($this->user()->hasRole('admin'), 'boolean'),
            'aktivan' => Rule::when($this->user()->hasRole('admin'), 'boolean'),

            // Taxonomies
            'programi_njege' => 'sometimes|array|min:1',
            'programi_njege.*' => 'exists:programi_njege,id',
            'medicinske_usluge' => 'sometimes|array|min:1',
            'medicinske_usluge.*' => 'exists:medicinske_usluge,id',
            'smjestaj_uslovi' => 'nullable|array',
            'smjestaj_uslovi.*' => 'exists:smjestaj_uslovi,id',

            // FAQ
            'faqs' => 'nullable|array|max:20',
            'faqs.*.pitanje' => 'required_with:faqs|string|max:500',
            'faqs.*.odgovor' => 'required_with:faqs|string|max:2000',

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
            'naziv.required' => 'Naziv doma je obavezan',
            'naziv.unique' => 'Dom sa ovim nazivom već postoji',
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
            'tip_doma_id.required' => 'Tip doma je obavezan',
            'tip_doma_id.exists' => 'Odabrani tip doma ne postoji',
            'nivo_njege_id.required' => 'Nivo njege je obavezan',
            'nivo_njege_id.exists' => 'Odabrani nivo njege ne postoji',
            'nurses_availability.required' => 'Dostupnost medicinskih sestara je obavezna',
            'nurses_availability.in' => 'Nevalidna opcija za dostupnost sestara',
            'doctor_availability.required' => 'Dostupnost ljekara je obavezna',
            'doctor_availability.in' => 'Nevalidna opcija za dostupnost ljekara',
            'pricing_mode.required' => 'Način prikazivanja cijene je obavezan',
            'pricing_mode.in' => 'Nevalidna opcija za način prikazivanja cijene',
            'price_from.min' => 'Cijena ne može biti negativna',
            'programi_njege.required' => 'Morate odabrati najmanje jedan program njege',
            'programi_njege.min' => 'Morate odabrati najmanje jedan program njege',
            'programi_njege.*.exists' => 'Odabrani program njege ne postoji',
            'medicinske_usluge.required' => 'Morate odabrati najmanje jednu medicinsku uslugu',
            'medicinske_usluge.min' => 'Morate odabrati najmanje jednu medicinsku uslugu',
            'medicinske_usluge.*.exists' => 'Odabrana medicinska usluga ne postoji',
            'smjestaj_uslovi.*.exists' => 'Odabrani uslov smještaja ne postoji',
            'faqs.max' => 'Možete dodati maksimalno 20 FAQ pitanja',
            'faqs.*.pitanje.required_with' => 'Pitanje je obavezno',
            'faqs.*.pitanje.max' => 'Pitanje može imati maksimalno 500 karaktera',
            'faqs.*.odgovor.required_with' => 'Odgovor je obavezan',
            'faqs.*.odgovor.max' => 'Odgovor može imati maksimalno 2000 karaktera',
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

        // Normalize relation payloads from dashboard (array of objects -> array of IDs)
        $normalizeIds = function ($value): array {
            if (!is_array($value)) {
                return [];
            }

            return collect($value)
                ->map(function ($item) {
                    if (is_array($item) && isset($item['id'])) {
                        return (int) $item['id'];
                    }
                    if (is_object($item) && isset($item->id)) {
                        return (int) $item->id;
                    }
                    if (is_numeric($item)) {
                        return (int) $item;
                    }
                    return null;
                })
                ->filter(fn ($id) => is_int($id) && $id > 0)
                ->values()
                ->all();
        };

        if ($this->has('programi_njege')) {
            $this->merge([
                'programi_njege' => $normalizeIds($this->input('programi_njege'))
            ]);
        }

        if ($this->has('smjestaj_uslovi')) {
            $this->merge([
                'smjestaj_uslovi' => $normalizeIds($this->input('smjestaj_uslovi'))
            ]);
        }

        if ($this->has('medicinske_usluge')) {
            $this->merge([
                'medicinske_usluge' => $normalizeIds($this->input('medicinske_usluge'))
            ]);
        } elseif ($this->has('medicinsk_usluge')) {
            // Backward compatibility with serialized relation name from Eloquent.
            $this->merge([
                'medicinske_usluge' => $normalizeIds($this->input('medicinsk_usluge'))
            ]);
        }

        // Auto-add https:// to website if missing protocol
        if ($this->website && !preg_match('/^https?:\/\//i', $this->website)) {
            $this->merge([
                'website' => 'https://' . $this->website
            ]);
        }

        // Convert boolean strings to actual booleans
        $booleanFields = [
            'has_physiotherapist', 'has_physiatrist', 'emergency_protocol',
            'controlled_entry', 'video_surveillance', 'online_upit', 'verifikovan', 'aktivan'
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
