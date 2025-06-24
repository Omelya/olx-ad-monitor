<?php

namespace OlxMonitor\Repository;

use OlxMonitor\Entity\PriceHistory;

interface PriceHistoryRepositoryInterface
{
    public function save(PriceHistory $priceHistory): void;

    public function findByListingId(string $listingId): array;

    public function findById(string $id): ?PriceHistory;

    public function delete(PriceHistory $priceHistory): void;
}
