<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryListResource extends JsonResource
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
            'adresa' => $this->adresa,
            'telefon' => $this->telefon,
            'email' => $this->email,
            'featured_slika' => $this->featured_slika,
            'profilna_slika' => $this->profilna_slika,
            'kratak_opis' => $this->kratak_opis,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'online_rezultati' => $this->online_rezultati,
            'prosjecno_vrijeme_rezultata' => $this->prosjecno_vrijeme_rezultata,
            'prosjecna_ocjena' => $this->prosjecna_ocjena ? round($this->prosjecna_ocjena, 2) : 0,
            'broj_recenzija' => $this->broj_recenzija ?? 0,
            'verifikovan' => $this->verifikovan,
        ];
    }
}
