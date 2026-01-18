<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorListResource extends JsonResource
{
    /**
     * Transform the resource into an array for listing pages.
     * Only includes essential fields to reduce payload size.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ime' => $this->ime,
            'prezime' => $this->prezime,
            'slug' => $this->slug,
            'specijalnost' => $this->specijalnost,
            'specijalnost_id' => $this->specijalnost_id,
            'grad' => $this->grad,
            'lokacija' => $this->lokacija,
            'telefon' => $this->telefon,
            'slika_profila' => $this->slika_profila,
            'ocjena' => $this->ocjena ? round($this->ocjena, 1) : 0,
            'broj_ocjena' => $this->broj_ocjena ?? 0,
            'prihvata_online' => $this->prihvata_online,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            // Relationships (only if loaded)
            'klinika' => $this->whenLoaded('klinika', function () {
                return [
                    'id' => $this->klinika->id,
                    'naziv' => $this->klinika->naziv,
                    'slug' => $this->klinika->slug,
                    'grad' => $this->klinika->grad,
                ];
            }),

            'specijalnost_model' => $this->whenLoaded('specijalnostModel', function () {
                return [
                    'id' => $this->specijalnostModel->id,
                    'naziv' => $this->specijalnostModel->naziv,
                    'slug' => $this->specijalnostModel->slug,
                ];
            }),
        ];
    }
}
