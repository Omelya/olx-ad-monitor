<?php

namespace OlxMonitor\Service;

use OlxMonitor\Repository\ListingRepositoryInterface;
use OlxMonitor\Repository\SearchFilterRepositoryInterface;

class StatisticsService
{
    public function __construct(
        private ListingRepositoryInterface $listingRepository,
        private SearchFilterRepositoryInterface $filterRepository,
    ) {}

    public function getDashboardStats(): array
    {
        $allListings = $this->listingRepository->findAll();
        $activeListings = array_filter($allListings, fn($l) => $l->isActive);
        $filters = $this->filterRepository->findActive();

        $priceStats = $this->calculatePriceStatistics($activeListings);
        $categoryStats = $this->calculateCategoryStatistics($activeListings);

        return [
            'total_listings' => count($allListings),
            'active_listings' => count($activeListings),
            'removed_listings' => count($allListings) - count($activeListings),
            'total_filters' => count($filters),
            'price_stats' => $priceStats,
            'category_stats' => $categoryStats,
            'recent_listings' => array_slice($allListings, 0, 10),
        ];
    }

    private function calculatePriceStatistics(array $listings): array
    {
        if (empty($listings)) {
            return [
                'min_price' => 0,
                'max_price' => 0,
                'avg_price' => 0,
                'median_price' => 0,
            ];
        }

        $prices = array_column($listings, 'price');
        sort($prices);

        $count = count($prices);
        $median = $count % 2 === 0
            ? ($prices[$count / 2 - 1] + $prices[$count / 2]) / 2
            : $prices[(int)($count / 2)];

        return [
            'min_price' => min($prices),
            'max_price' => max($prices),
            'avg_price' => array_sum($prices) / $count,
            'median_price' => $median,
        ];
    }

    private function calculateCategoryStatistics(array $listings): array
    {
        $stats = [];

        foreach ($listings as $listing) {
            // This would require storing category info with listings
            // or joining with filters table
            $category = 'unknown'; // Would be retrieved from filter relationship

            if (!isset($stats[$category])) {
                $stats[$category] = [
                    'count' => 0,
                    'avg_price' => 0,
                    'total_price' => 0,
                ];
            }

            $stats[$category]['count']++;
            $stats[$category]['total_price'] += $listing->price;
            $stats[$category]['avg_price'] = $stats[$category]['total_price'] / $stats[$category]['count'];
        }

        return $stats;
    }
}
