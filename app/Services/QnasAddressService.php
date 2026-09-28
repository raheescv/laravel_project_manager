<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Qatar National Address Service — the zone / street / building data behind the
 * blue "Inwani" plates, used to pick a delivery address at storefront checkout.
 *
 * The token is tied to one registered domain and QNAS allows only 60 calls a
 * minute, so the browser never talks to QNAS directly: lists are fetched here and
 * cached (the geography barely changes). A failed call is never cached, and
 * returns null so the storefront can fall back to typing the numbers in.
 *
 * @see https://qnas.qa/apidoc
 */
class QnasAddressService
{
    private const ZONES_TTL_DAYS = 30;

    private const STREETS_TTL_DAYS = 7;

    public function isConfigured(): bool
    {
        return filled(config('services.qnas.token')) && filled(config('services.qnas.domain'));
    }

    /**
     * Every zone, one row per number. QNAS lists a zone once per district it
     * covers (zone 5 is Fereej Al Asmakh, Barahat Al Jufairi and Al Najada), so
     * those names are joined.
     *
     * @return list<array{number: int, name_en: string, name_ar: string}>|null
     */
    public function zones(): ?array
    {
        return $this->remember('qnas:zones', self::ZONES_TTL_DAYS, function (): ?array {
            $rows = $this->get('get_zones');
            if ($rows === null) {
                return null;
            }

            $zones = [];
            foreach ($rows as $row) {
                $number = (int) ($row['zone_number'] ?? 0);
                if ($number <= 0) {
                    continue;
                }
                $zones[$number]['number'] = $number;
                $zones[$number]['en'][] = $this->name($row['zone_name_en'] ?? null);
                $zones[$number]['ar'][] = $this->name($row['zone_name_ar'] ?? null);
            }
            ksort($zones);

            return array_values(array_map(fn (array $zone): array => [
                'number' => $zone['number'],
                'name_en' => implode(' · ', array_unique(array_filter($zone['en']))),
                'name_ar' => implode(' · ', array_unique(array_filter($zone['ar']))),
            ], $zones));
        });
    }

    /**
     * Streets in a zone. Most small streets are unnamed ("0" in QNAS).
     *
     * @return list<array{number: int, name_en: string|null, name_ar: string|null}>|null
     */
    public function streets(int $zone): ?array
    {
        return $this->remember("qnas:streets:{$zone}", self::STREETS_TTL_DAYS, function () use ($zone): ?array {
            $rows = $this->get("get_streets/{$zone}");
            if ($rows === null) {
                return null;
            }

            $streets = collect($rows)
                ->map(fn (array $row): array => [
                    'number' => (int) ($row['street_number'] ?? 0),
                    'name_en' => $this->name($row['street_name_en'] ?? null),
                    'name_ar' => $this->name($row['street_name_ar'] ?? null),
                ])
                ->filter(fn (array $street): bool => $street['number'] > 0)
                ->unique('number')
                ->sortBy('number')
                ->values()
                ->all();

            return $streets;
        });
    }

    /**
     * Buildings on a street with their map position (QNAS calls latitude `x`).
     *
     * @return list<array{number: string, lat: float|null, lng: float|null}>|null
     */
    public function buildings(int $zone, int $street): ?array
    {
        return $this->remember("qnas:buildings:{$zone}:{$street}", self::STREETS_TTL_DAYS, function () use ($zone, $street): ?array {
            $rows = $this->get("get_buildings/{$zone}/{$street}");
            if ($rows === null) {
                return null;
            }

            $buildings = collect($rows)
                ->map(fn (array $row): array => [
                    'number' => trim((string) ($row['building_number'] ?? '')),
                    'lat' => is_numeric($row['x'] ?? null) ? round((float) $row['x'], 7) : null,
                    'lng' => is_numeric($row['y'] ?? null) ? round((float) $row['y'], 7) : null,
                ])
                ->filter(fn (array $building): bool => $building['number'] !== '')
                ->unique('number')
                ->sortBy('number', SORT_NATURAL)
                ->values()
                ->all();

            return $buildings;
        });
    }

    /**
     * Where a building is, from lists already fetched while the customer picked
     * it. Never calls QNAS — a checkout must not wait on (or fail because of) it.
     *
     * @return array{lat: float, lng: float}|null
     */
    public function cachedLocation(string $zone, string $street, string $building): ?array
    {
        if (! ctype_digit($zone) || ! ctype_digit($street)) {
            return null;
        }

        $match = collect(Cache::get("qnas:buildings:{$zone}:{$street}", []))
            ->first(fn (array $row): bool => strcasecmp($row['number'], $building) === 0);

        if (! $match || $match['lat'] === null || $match['lng'] === null) {
            return null;
        }

        return ['lat' => $match['lat'], 'lng' => $match['lng']];
    }

    /**
     * Cache a successful fetch only, so a QNAS outage heals on the next request.
     *
     * @param  callable(): (array|null)  $fetch
     */
    private function remember(string $key, int $days, callable $fetch): ?array
    {
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $fresh = $fetch();
        if ($fresh !== null) {
            Cache::put($key, $fresh, now()->addDays($days));
        }

        return $fresh;
    }

    /** @return array<int, array<string, mixed>>|null */
    private function get(string $path): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.qnas.base_url'), '/'))
                ->withHeaders([
                    'X-Token' => config('services.qnas.token'),
                    'X-Domain' => config('services.qnas.domain'),
                ])
                ->acceptJson()
                ->timeout(10)
                ->get($path);
        } catch (ConnectionException $e) {
            Log::warning('QNAS unreachable', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }

        $body = $response->json();
        // Errors come back as {"status":"error","message":…} or {"message":…}; lists are bare arrays.
        if (! $response->successful() || ! is_array($body) || ! array_is_list($body)) {
            Log::warning('QNAS request failed', ['path' => $path, 'status' => $response->status(), 'body' => $body]);

            return null;
        }

        return $body;
    }

    /** QNAS writes "0" for a missing name. */
    private function name(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || $value === '0' ? null : $value;
    }
}
