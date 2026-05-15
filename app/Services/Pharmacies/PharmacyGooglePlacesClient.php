<?php

namespace App\Services\Pharmacies;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Arr;
use RuntimeException;

class PharmacyGooglePlacesClient
{
    private const BASE_URL = 'https://places.googleapis.com/v1';

    public function __construct(private readonly HttpFactory $http)
    {
    }

    /**
     * @return array{places: array<int, array<string, mixed>>, next_page_token: string|null}
     */
    public function textSearch(string $query, int $maxResults = 20, ?string $pageToken = null): array
    {
        $body = [
            'textQuery' => $query,
            'pageSize' => max(1, min($maxResults, 20)),
            'languageCode' => 'bs',
            'regionCode' => 'BA',
            'includedType' => 'pharmacy',
            'strictTypeFiltering' => true,
        ];

        if ($pageToken !== null && trim($pageToken) !== '') {
            $body['pageToken'] = $pageToken;
        }

        $response = $this->http
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey(),
                'X-Goog-FieldMask' => implode(',', [
                    'places.id',
                    'places.displayName',
                    'places.formattedAddress',
                    'places.location',
                    'places.googleMapsUri',
                    'places.types',
                    'nextPageToken',
                ]),
            ])
            ->retry(3, 500)
            ->post(self::BASE_URL . '/places:searchText', $body);

        $response->throw();

        return [
            'places' => Arr::wrap($response->json('places')),
            'next_page_token' => $this->stringValue($response->json('nextPageToken')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function details(string $placeId): array
    {
        $response = $this->http
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey(),
                'X-Goog-FieldMask' => implode(',', [
                    'id',
                    'displayName',
                    'formattedAddress',
                    'location',
                    'nationalPhoneNumber',
                    'internationalPhoneNumber',
                    'regularOpeningHours',
                    'websiteUri',
                    'googleMapsUri',
                    'types',
                ]),
            ])
            ->retry(3, 500)
            ->get(self::BASE_URL . '/places/' . rawurlencode($placeId));

        $response->throw();

        return $response->json() ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function addressDetails(string $placeId): array
    {
        $response = $this->http
            ->withHeaders([
                'X-Goog-Api-Key' => $this->apiKey(),
                'X-Goog-FieldMask' => implode(',', [
                    'id',
                    'formattedAddress',
                    'addressComponents',
                ]),
            ])
            ->retry(3, 500)
            ->get(self::BASE_URL . '/places/' . rawurlencode($placeId));

        $response->throw();

        return $response->json() ?? [];
    }

    private function apiKey(): string
    {
        $apiKey = (string) env('GOOGLE_MAPS_API_KEY', '');

        if (trim($apiKey) === '') {
            throw new RuntimeException('GOOGLE_MAPS_API_KEY nije postavljen.');
        }

        return $apiKey;
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
