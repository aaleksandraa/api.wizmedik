<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lijek extends Model
{
    use HasFactory;

    protected $table = 'lijekovi';

    protected $fillable = [
        'lijek_id',
        'slug',
        'atc_sifra',
        'naziv_atc5_nivo',
        'naziv',
        'brend',
        'oblik',
        'doza',
        'pakovanje',
        'jedinica_mjere',
        'sifra_projekta',
        'opis',
        'inn',
        'jidl',
        'naziv_lijeka',
        'proizvodjac',
        'nosilac_dozvole',
        'oblik_registar',
        'jacina',
        'pakovanje_registar',
        'broj_dozvole',
        'tip_lijeka',
        'podtip_lijeka',
        'vazi_od',
        'vazi_do',
        'datum_rjesenja',
        'rezim_izdavanja',
        'posebne_oznake',
        'nalaz_prve_serije',
        'nalaz_prve_serije_prethodno_rjesenje',
        'farmaceutski_oblik',
        'vrsta_lijeka',
        'lista_rfzo_pojasnjenje',
        'aktuelna_cijena',
        'aktuelni_procenat_participacije',
        'aktuelni_iznos_participacije',
        'aktuelna_lista_id',
        'aktuelna_verzija_od',
        'aktuelna_verzija_do',
        'aktuelni_broj_indikacija',
        'xml_datum_izvoza',
    ];

    protected $casts = [
        'vazi_od' => 'date',
        'vazi_do' => 'date',
        'datum_rjesenja' => 'date',
        'aktuelna_cijena' => 'decimal:2',
        'aktuelni_procenat_participacije' => 'decimal:2',
        'aktuelni_iznos_participacije' => 'decimal:2',
        'aktuelna_verzija_od' => 'date',
        'aktuelna_verzija_do' => 'date',
        'xml_datum_izvoza' => 'date',
        'aktuelni_broj_indikacija' => 'integer',
    ];

    public function fondZapisi(): HasMany
    {
        return $this->hasMany(LijekFondZapis::class, 'lijek_id');
    }

    public function registarZapisi(): HasMany
    {
        return $this->hasMany(LijekRegistarZapis::class, 'lijek_id');
    }

    public function scopeSearch($query, string $term)
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        $needle = '%' . mb_strtolower($term) . '%';

        return $query->where(function ($q) use ($needle, $term) {
            $q->whereRaw("LOWER(COALESCE(naziv, '')) LIKE ?", [$needle])
                ->orWhereRaw("LOWER(COALESCE(naziv_lijeka, '')) LIKE ?", [$needle])
                ->orWhereRaw("LOWER(COALESCE(brend, '')) LIKE ?", [$needle])
                ->orWhereRaw("LOWER(COALESCE(atc_sifra, '')) LIKE ?", [$needle])
                ->orWhereRaw("LOWER(COALESCE(inn, '')) LIKE ?", [$needle]);

            if (is_numeric($term)) {
                $q->orWhere('lijek_id', (int) $term);
            }
        });
    }

    public static function defaultListaPojasnjenje(?string $listaId): ?string
    {
        if (!$listaId) {
            return null;
        }

        $code = strtoupper(trim($listaId));
        if ($code === '') {
            return null;
        }

        return RfzoLista::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->value('pojasnjenje');
    }

    public function resolveListaPojasnjenje(): ?string
    {
        return $this->lista_rfzo_pojasnjenje ?: self::defaultListaPojasnjenje($this->aktuelna_lista_id);
    }
}
