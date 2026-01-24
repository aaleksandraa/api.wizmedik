<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pitanje;
use App\Models\Doktor;
use App\Models\Klinika;
use App\Models\Grad;
use App\Models\Specijalnost;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    private $baseUrl = 'https://wizmedik.com';

    public function index()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ';
        $xml .= 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

        // Static pages - highest priority
        $xml .= $this->addUrl('/', now(), '1.0', 'daily');
        $xml .= $this->addUrl('/doktori', now(), '0.95', 'daily');
        $xml .= $this->addUrl('/klinike', now(), '0.95', 'daily');
        $xml .= $this->addUrl('/gradovi', now(), '0.9', 'weekly');
        $xml .= $this->addUrl('/specijalnosti', now(), '0.9', 'weekly');
        $xml .= $this->addUrl('/pitanja', now(), '0.85', 'daily');
        $xml .= $this->addUrl('/kalkulatori', now(), '0.85', 'weekly');
        $xml .= $this->addUrl('/postavi-pitanje', now(), '0.7', 'monthly');

        // Doctors - high priority individual pages
        $doktori = Doktor::orderBy('ocjena', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($doktori as $doktor) {
            $xml .= $this->addUrl(
                '/doktor/' . $doktor->slug,
                $doktor->updated_at,
                '0.85',
                'weekly',
                $doktor->slika_profila
            );
        }

        // Clinics
        $klinike = Klinika::where('aktivan', true)
            ->orderBy('ocjena', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($klinike as $klinika) {
            $slike = is_array($klinika->slike) ? $klinika->slike : [];
            $xml .= $this->addUrl(
                '/klinika/' . $klinika->slug,
                $klinika->updated_at,
                '0.8',
                'weekly',
                $slike[0] ?? null
            );
        }

        // Cities
        $gradovi = Grad::where('aktivan', true)->get();
        foreach ($gradovi as $grad) {
            $xml .= $this->addUrl(
                '/grad/' . $grad->slug,
                $grad->updated_at,
                '0.8',
                'weekly'
            );
        }

        // Specialties
        $specijalnosti = Specijalnost::whereNull('parent_id')->get();
        foreach ($specijalnosti as $spec) {
            $xml .= $this->addUrl(
                '/specijalnost/' . $spec->slug,
                $spec->updated_at,
                '0.8',
                'weekly'
            );

            // Child specialties
            $children = Specijalnost::where('parent_id', $spec->id)->get();
            foreach ($children as $child) {
                $xml .= $this->addUrl(
                    '/specijalnost/' . $child->slug,
                    $child->updated_at,
                    '0.75',
                    'weekly'
                );
            }
        }

        // City + Specialty combinations (high SEO value)
        foreach ($gradovi as $grad) {
            foreach ($specijalnosti as $spec) {
                // Check if there are doctors for this combination
                $count = Doktor::where('grad', $grad->naziv)
                    ->where(function($q) use ($spec) {
                        $q->where('specijalnost_id', $spec->id)
                          ->orWhereHas('specijalnosti', function($sq) use ($spec) {
                              $sq->where('specijalnost_id', $spec->id);
                          });
                    })
                    ->count();

                if ($count > 0) {
                    $xml .= $this->addUrl(
                        '/doktori/' . $grad->slug . '/' . $spec->slug,
                        now(),
                        '0.75',
                        'weekly'
                    );
                }
            }

            // City only doctors page
            $xml .= $this->addUrl(
                '/doktori/' . $grad->slug,
                now(),
                '0.7',
                'weekly'
            );
        }

        // Specialty only doctors pages
        foreach ($specijalnosti as $spec) {
            $xml .= $this->addUrl(
                '/doktori/specijalnost/' . $spec->slug,
                now(),
                '0.7',
                'weekly'
            );
        }

        // Clinics by specialty
        foreach ($specijalnosti as $spec) {
            $xml .= $this->addUrl(
                '/klinike/specijalnost/' . $spec->slug,
                now(),
                '0.65',
                'weekly'
            );
        }

        // Questions/Pitanja
        $pitanja = Pitanje::javna()
            ->orderBy('updated_at', 'desc')
            ->limit(500)
            ->get();

        foreach ($pitanja as $pitanje) {
            $xml .= $this->addUrl(
                '/pitanja/' . $pitanje->slug,
                $pitanje->updated_at,
                '0.6',
                'monthly'
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function addUrl($path, $lastmod, $priority, $changefreq, $image = null)
    {
        $url = '<url>';
        $url .= '<loc>' . htmlspecialchars($this->baseUrl . $path) . '</loc>';
        $url .= '<lastmod>' . $lastmod->toAtomString() . '</lastmod>';
        $url .= '<priority>' . $priority . '</priority>';
        $url .= '<changefreq>' . $changefreq . '</changefreq>';

        if ($image) {
            $url .= '<image:image>';
            $url .= '<image:loc>' . htmlspecialchars($image) . '</image:loc>';
            $url .= '</image:image>';
        }

        $url .= '</url>';
        return $url;
    }
}
