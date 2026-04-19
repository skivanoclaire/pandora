<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Reverse geocode koordinat ke nama lokasi via Nominatim (OpenStreetMap).
     * Gratis, tanpa API key. Rate limit: 1 req/detik (cache mengurangi hit).
     *
     * @return array{kota: ?string, provinsi: ?string, negara: ?string, display: ?string}
     */
    public function reverseGeocode(float $lat, float $lon): array
    {
        $default = ['kota' => null, 'provinsi' => null, 'negara' => null, 'display' => null];

        // Round ke 4 desimal (~11m precision) untuk cache key yang efektif
        $cacheKey = 'geocode:' . round($lat, 4) . ',' . round($lon, 4);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($lat, $lon, $default) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'PANDORA-Kaltara/1.0 (pandora.kaltaraprov.go.id)'])
                    ->get('https://nominatim.openstreetmap.org/reverse', [
                        'lat' => $lat,
                        'lon' => $lon,
                        'format' => 'json',
                        'zoom' => 10, // level kota/kabupaten
                        'addressdetails' => 1,
                    ]);

                if ($response->failed()) {
                    return $default;
                }

                $data = $response->json();
                $addr = $data['address'] ?? [];

                $kota = $addr['city'] ?? $addr['town'] ?? $addr['county']
                    ?? $addr['municipality'] ?? $addr['city_district'] ?? null;
                $provinsi = $addr['state'] ?? null;
                $negara = $addr['country'] ?? null;

                // Build display string
                $parts = array_filter([$kota, $provinsi]);
                $display = !empty($parts) ? implode(', ', $parts) : ($data['display_name'] ?? null);

                return [
                    'kota' => $kota,
                    'provinsi' => $provinsi,
                    'negara' => $negara,
                    'display' => $display,
                ];
            } catch (\Throwable $e) {
                Log::warning("Reverse geocode failed: {$e->getMessage()}");
                return $default;
            }
        });
    }

    /**
     * Cek apakah koordinat berada di wilayah Kalimantan Utara.
     * Bounding box: lat 1.0-4.5, lon 115.0-118.0
     * Mencakup Malinau selatan (lat ~1.3) dan Krayan barat (lng ~115.1)
     */
    public function isInKaltara(float $lat, float $lon): bool
    {
        return $lat >= 1.0 && $lat <= 4.5 && $lon >= 115.0 && $lon <= 118.0;
    }
}
