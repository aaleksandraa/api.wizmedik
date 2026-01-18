<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaListResource extends JsonResource
{
    /**
     * Transform the resource into an array for listing pages.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'naziv' => $this->naziv,
            'slug' => $this->slug,
            'grad' => $this->grad,
            'regija' => $this->regija,
            'adresa' => $this->adresa,
            'telefon' => $this->telefon,
            'email' => $this->email,
            'featured_slika' => $this->featured_slika,
            'opis' => $this->opis,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'medicinski_nadzor' => $this->medicinski_nadzor,
            'fizijatar_prisutan' => $this->fizijatar_prisutan,
            'ima_smjestaj' => $this->ima_smjestaj,
            'online_rezervacija' => $this->online_rezervacija,
            'prosjecna_ocjena' => $this->prosjecna_ocjena ? round($this->prosjecna_ocjena, 2) : 0,
            'broj_recenzija' => $this->broj_recenzija ?? 0,
            'verifikovan' => $this->verifikovan,

            // Relationships (only if loaded)
            'vrste' => $this->whenLoaded('vrste', function () {
                return $this->vrste->map(function ($vrsta) {
                    return [
                        'id' => $vrsta->id,
                        'naziv' => $vrsta->naziv,
                        'slug' => $vrsta->slug,
                        'ikona' => $vrsta->ikona,
                    ];
                });
            }),
        ];
    }
}
