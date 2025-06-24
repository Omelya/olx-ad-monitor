<?php

namespace OlxMonitor\Http;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use OlxMonitor\Entity\SearchFilter;
use Psr\Log\LoggerInterface;
use OlxMonitor\Exception\OlxApiException;

class OlxApiClient
{
    private const int RESPONSE_LIMIT = 40;

    private const string CURRENCY = 'UAH';

    private const string SEARCH_QUERY = '
        query ListingSearchQuery($searchParameters: [SearchParameter!]) {
            clientCompatibleListings(searchParameters: $searchParameters) {
                __typename
                ... on ListingSuccess {
                    __typename
                    data {
                        id
                        title
                        description
                        url
                        location {
                            city {
                                name
                            }
                            region {
                                name
                            }
                        }
                        photos {
                            link
                        }
                        created_time
                        last_refresh_time
                        params {
                            key
                            value {
                                __typename
                                ... on PriceParam {
                                    value
                                    currency
                                }
                            }
                        }
                    }
                    metadata {
                        total_elements
                    }
                }
                ... on ListingError {
                    __typename
                    error {
                        code
                        detail
                        status
                        title
                    }
                }
            }
        }
    ';

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string          $apiUrl,
    ) {}

    /**
     * @throws OlxApiException
     * @throws \JsonException
     */
    public function searchListings(SearchFilter $filter): array
    {
        $offset = 0;
        $listings = [];

        while (true) {
            try {
                $variables = $this->buildSearchVariables($filter, $offset);

                $response = $this->httpClient->request('POST', $this->apiUrl, [
                    'json' => [
                        'query' => self::SEARCH_QUERY,
                        'variables' => $variables,
                    ],
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'Mozilla/5.0 (compatible; OLX Monitor)',
                    ],
                ]);

                $data = json_decode(
                    $response->getBody()->getContents(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );

                if (isset($data['errors'])) {
                    throw new OlxApiException('GraphQL errors: ' . json_encode($data['errors'], JSON_THROW_ON_ERROR));
                }

                $responseData = $data['data']['clientCompatibleListings'] ?? null;

                if (!$responseData || $responseData['__typename'] === 'ListingError') {
                    $errorMessage = $responseData['error']['detail'] ?? 'Unknown error';
                    throw new OlxApiException('Listing error: ' . $errorMessage);
                }

                $result = $this->transformListings($responseData['data'] ?? []);

                $listings = [...$listings, ...$result];

                if (count($result) < self::RESPONSE_LIMIT) {
                    break;
                }

                $offset += self::RESPONSE_LIMIT;
            } catch (GuzzleException $e) {
                $this->logger->error('OLX API request failed', [
                    'error' => $e->getMessage(),
                    'filter' => $filter->name,
                ]);

                throw new OlxApiException('API request failed: ' . $e->getMessage(), 0, $e);
            }
        }

        return $listings;
    }

    private function buildSearchVariables(SearchFilter $filter, int $offset = 0): array
    {
        $categoryId = $this->getCategoryId($filter->category, $filter->subcategory, $filter->type);

        return [
            'searchParameters' => [
                ['key' => 'category_id', 'value' => (string) $categoryId],
                ['key' => 'region_id', 'value' => (string) $filter->filters['region_id']],
                ['key' => 'city_id', 'value' => (string) $filter->filters['city_id']],
                ['key' => 'distance', 'value' => (string) $filter->filters['distance']],
                ['key' => 'currency', 'value' => self::CURRENCY],
                ['key' => 'limit', 'value' => (string) self::RESPONSE_LIMIT],
                ['key' => 'offset', 'value' => (string) $offset],
                ...$this->prepareApartmentType($filter->filters['apartment_type'] ?? ''),
                ...$this->prepareArea($filter->filters['area'] ?? ''),
                ...$this->preparePrice($filter->filters['price'] ?? '')
            ],
        ];
    }

    private function getCategoryId(string $category, string $subcategory, ?string $type): int
    {
        $categoryMap = [
            'нерухомість' => [
                'квартири' => [
                    'довгострокова оренда' => 1760,
                    'продаж' => 1758,
                ],
                'будинки' => [
                    'продаж' => 1309,
                    'оренда' => 1310,
                ],
            ],
            'транспорт' => [
                'легкові автомобілі' => 1318,
                'мотоцикли' => 1319,
            ],
        ];

        if ($type) {
            return $categoryMap[$category][$subcategory][$type] ?? 0;
        }

        return $categoryMap[$category][$subcategory] ?? 0;
    }

    private function getApartmentType(string $type): ?int
    {
        $apartmentTypeMap = [
            'царський будинок' => 1,
            'житловий фонд 2001-2010' => 10,
            'житловий фонд від 2011' => 11,
        ];

        return $apartmentTypeMap[$type] ?? null;
    }

    private function prepareApartmentType(string $rawType): array
    {
        if (empty($rawType)) {
            return [];
        }

        $filters = [];
        $key = 0;

        $items = explode(',', $rawType);

        foreach ($items as $item) {
            $item = trim($item);
            $type = $this->getApartmentType($item);

            if ($type) {
                $filters[] = [
                    'key' => "filter_enum_property_type_appartments_sale[$key]",
                    'value' => (string) $type,
                ];

                $key++;
            }
        }

        return $filters;
    }

    private function prepareArea(string $rawArea): array
    {
        if (empty($rawArea)) {
            return [];
        }

        $areas = explode(',', trim($rawArea));
        $filters = [];

        foreach ($areas as $index => $area) {
            $area = trim($area);

            if (empty($area)) {
                continue;
            }

            $key = $index === 0 ? 'from' : 'to';

            $filters[] = [
                'key' => "filter_float_total_area:$key",
                'value' => (string) (int) $area,
            ];
        }

        return $filters;
    }

    private function preparePrice(string $rawPrices): array
    {
        if (empty($rawPrices)) {
            return [];
        }

        $prices = explode(',', trim($rawPrices));
        $filters = [];

        foreach ($prices as $index => $price) {
            $price = trim($price);
            if (empty($price)) {
                continue;
            }

            $key = $index === 0 ? 'from' : 'to';

            $filters[] = [
                'key' => "filter_float_price:$key",
                'value' => (string) (int) $price,
            ];
        }

        return $filters;
    }

    private function transformListings(array $data): array
    {
        return array_map(static function ($listing) {
            $price = 0;
            $currency = 'UAH';

            foreach ($listing['params'] ?? [] as $param) {
                if ($param['key'] === 'price' &&
                    isset($param['value']['__typename']) &&
                    $param['value']['__typename'] === 'PriceParam'
                ) {
                    $price = (float) ($param['value']['value'] ?? 0);
                    $currency = $param['value']['currency'] ?? 'UAH';

                    break;
                }
            }

            $location = ($listing['location']['city']['name'] ?? '') . ', ' . ($listing['location']['region']['name'] ?? '');

            $images = array_map(
                static fn($photo) => str_replace(['{width}', '{height}'], ['800', '600'], $photo['link']),
                $listing['photos'] ?? [],
            );

            return [
                'id' => $listing['id'],
                'title' => $listing['title'],
                'description' => $listing['description'] ?? '',
                'price' => $price,
                'currency' => $currency,
                'url' => $listing['url'],
                'location' => $location,
                'images' => $images,
                'published_at' => $listing['created_time'],
                'refreshed_at' => $listing['last_refresh_time'],
            ];
        }, $data);
    }
}
