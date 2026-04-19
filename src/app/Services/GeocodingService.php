<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Nominatim base URL — self-hosted (tanpa rate limit) dengan fallback ke public.
     */
    private function baseUrl(): string
    {
        return env('NOMINATIM_URL', 'http://pandora-nominatim:8080');
    }

    /**
     * Timestamp request terakhir (rate limit untuk fallback public Nominatim).
     */
    private static float $lastRequestTime = 0;

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

        // Cek cache dulu — hanya return jika hasilnya valid (bukan null yang ter-cache dari error)
        $cached = Cache::get($cacheKey);
        if ($cached !== null && $cached['display'] !== null) {
            return $cached;
        }

        // Rate limit hanya untuk public Nominatim (self-hosted tidak perlu)
        $url = $this->baseUrl();
        $isSelfHosted = str_contains($url, 'pandora-nominatim');

        if (!$isSelfHosted) {
            $now = microtime(true);
            $elapsed = $now - self::$lastRequestTime;
            if ($elapsed < 1.1) {
                usleep((int) ((1.1 - $elapsed) * 1_000_000));
            }
            self::$lastRequestTime = microtime(true);
        }

        try {
            $response = Http::timeout($isSelfHosted ? 10 : 5)
                ->withHeaders(['User-Agent' => 'PANDORA-Kaltara/1.0 (pandora.kaltaraprov.go.id)'])
                ->get("{$url}/reverse", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'format' => 'json',
                    'zoom' => 10,
                    'addressdetails' => 1,
                ]);

            if ($response->failed()) {
                // Jangan cache hasil gagal (rate limit, server error)
                Log::warning("Geocode HTTP {$response->status()} for {$lat},{$lon}");
                return $default;
            }

            $data = $response->json();

            if (isset($data['error'])) {
                // Nominatim error (misal: "Unable to geocode") — jangan cache
                return $default;
            }

            $addr = $data['address'] ?? [];

            $kota = $addr['city'] ?? $addr['town'] ?? $addr['county']
                ?? $addr['municipality'] ?? $addr['city_district'] ?? null;
            $provinsi = $addr['state'] ?? null;
            $negara = $addr['country'] ?? null;

            // Build display string
            $parts = array_filter([$kota, $provinsi]);
            $display = !empty($parts) ? implode(', ', $parts) : ($data['display_name'] ?? null);

            $result = [
                'kota' => $kota,
                'provinsi' => $provinsi,
                'negara' => $negara,
                'display' => $display,
            ];

            // Hanya cache jika hasilnya valid
            if ($display !== null) {
                Cache::put($cacheKey, $result, now()->addDays(30));
            }

            return $result;
        } catch (\Throwable $e) {
            Log::warning("Reverse geocode failed: {$e->getMessage()}");
            return $default;
        }
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
