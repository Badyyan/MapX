<?php

namespace App\Services\Google;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read-side client for the Business Profile directory:
 * accounts via the Account Management API, locations via the Business
 * Information API (v1, readMask required).
 */
class GbpDirectory
{
    private const READ_MASK = 'name,title,storefrontAddress,phoneNumbers,websiteUri,categories,regularHours,latlng,metadata,languageCode';

    /** @return array<int, array{name: string, accountName: string, type: string}> */
    public function accounts(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://mybusinessaccountmanagement.googleapis.com/v1/accounts');

        if (! $response->successful()) {
            throw new RuntimeException('Failed to list Google Business accounts: '.$response->body());
        }

        return $response->json('accounts', []);
    }

    /**
     * All locations under an account, normalized for the import screen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function locations(string $accessToken, string $accountName): array
    {
        $locations = [];
        $pageToken = null;

        do {
            $response = Http::withToken($accessToken)
                ->get("https://mybusinessbusinessinformation.googleapis.com/v1/{$accountName}/locations", array_filter([
                    'readMask' => self::READ_MASK,
                    'pageSize' => 100,
                    'pageToken' => $pageToken,
                ]));

            if (! $response->successful()) {
                throw new RuntimeException('Failed to list Google Business locations: '.$response->body());
            }

            foreach ($response->json('locations', []) as $location) {
                $locations[] = $this->normalize($location, $accountName);
            }

            $pageToken = $response->json('nextPageToken');
        } while ($pageToken);

        return $locations;
    }

    /** Map a GBP location resource onto our branch fields. */
    private function normalize(array $location, string $accountName): array
    {
        $address = $location['storefrontAddress'] ?? [];

        return [
            'gbp_name' => $location['name'] ?? null,                    // "locations/{id}"
            'gbp_account' => $accountName,                              // "accounts/{id}"
            'title' => $location['title'] ?? null,
            'address' => implode(', ', $address['addressLines'] ?? []),
            'city' => $address['locality'] ?? null,
            'region' => $address['administrativeArea'] ?? null,
            'country' => $address['regionCode'] ?? 'SA',
            'lat' => $location['latlng']['latitude'] ?? null,
            'lng' => $location['latlng']['longitude'] ?? null,
            'phone' => $location['phoneNumbers']['primaryPhone'] ?? null,
            'website' => $location['websiteUri'] ?? null,
            'categories' => collect($location['categories']['additionalCategories'] ?? [])
                ->prepend($location['categories']['primaryCategory'] ?? null)
                ->filter()
                ->pluck('displayName')
                ->filter()
                ->values()
                ->all(),
            'hours' => $this->normalizeHours($location['regularHours']['periods'] ?? []),
            'place_id' => $location['metadata']['placeId'] ?? null,
            'maps_uri' => $location['metadata']['mapsUri'] ?? null,
        ];
    }

    private function normalizeHours(array $periods): ?array
    {
        $hours = [];
        foreach ($periods as $period) {
            $day = strtolower($period['openDay'] ?? '');
            if ($day && isset($period['openTime'], $period['closeTime'])) {
                $hours[$day] = [
                    'open' => $this->formatTime($period['openTime']),
                    'close' => $this->formatTime($period['closeTime']),
                ];
            }
        }

        return $hours ?: null;
    }

    private function formatTime(array $time): string
    {
        return sprintf('%02d:%02d', $time['hours'] ?? 0, $time['minutes'] ?? 0);
    }
}
