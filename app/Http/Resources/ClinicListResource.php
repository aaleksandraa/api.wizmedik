<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicListResource extends JsonResource
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
            'slike' => $this->slike,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'ocjena' => $this->ocjena ? round($this->ocjena, 1) : 0,
            'broj_ocjena' => $this->broj_ocjena ?? 0,

            // Count of doctors (if loaded)
            'broj_doktora' => $this->whenLoaded('doktori', function () {
                return $this->doktori->count();
            }),
        ];
    }
}
