<?php

namespace OlxMonitor\Service;

use DateTimeImmutable;
use OlxMonitor\Entity\Listing;
use OlxMonitor\Entity\PriceHistory;
use OlxMonitor\Repository\PriceHistoryRepositoryInterface;
use Ramsey\Uuid\Uuid;

class PriceHistoryService
{
    public function __construct(
        private PriceHistoryRepositoryInterface $priceHistoryRepository
    ) {}

    public function recordPriceChange(Listing $oldListing, Listing $newListing): void
    {
        if (abs($oldListing->price - $newListing->price) < 0.01) {
            return; // No significant price change
        }

        $priceHistory = new PriceHistory(
            id: Uuid::uuid4(),
            listingId: $oldListing->id,
            oldPrice: $oldListing->price,
            newPrice: $newListing->price,
            changedAt: new DateTimeImmutable(),
        );

        $this->priceHistoryRepository->save($priceHistory);
    }

    public function getPriceHistory(string $listingId): array
    {
        return $this->priceHistoryRepository->findByListingId($listingId);
    }

    public function getPriceStatistics(string $listingId): array
    {
        $history = $this->priceHistoryRepository->findByListingId($listingId);

        if (empty($history)) {
            return [
                'min_price' => null,
                'max_price' => null,
                'avg_price' => null,
                'price_changes' => 0,
                'total_change' => 0,
                'total_change_percent' => 0,
            ];
        }

        $prices = [];
        foreach ($history as $record) {
            $prices[] = $record->oldPrice;
            $prices[] = $record->newPrice;
        }

        $firstRecord = $history[0];
        $lastRecord = end($history);

        $totalChange = $lastRecord->newPrice - $firstRecord->oldPrice;
        $totalChangePercent = $firstRecord->oldPrice > 0
            ? ($totalChange / $firstRecord->oldPrice) * 100
            : 0;

        return [
            'min_price' => min($prices),
            'max_price' => max($prices),
            'avg_price' => array_sum($prices) / count($prices),
            'price_changes' => count($history),
            'total_change' => $totalChange,
            'total_change_percent' => round($totalChangePercent, 2),
            'first_price' => $firstRecord->oldPrice,
            'last_price' => $lastRecord->newPrice,
        ];
    }

    public function getRecentPriceChanges(int $limit = 10): array
    {
        // This would require a method in repository to get recent changes across all listings
        // For now, returning empty array as placeholder
        return [];
    }
}
