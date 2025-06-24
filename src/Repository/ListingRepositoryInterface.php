<?php

namespace OlxMonitor\Repository;

use OlxMonitor\Entity\Listing;

interface ListingRepositoryInterface
{
    public function save(Listing $listing): void;

    public function findByExternalId(string $externalId): ?Listing;

    public function findActiveByFilterId(string $filterId): array;

    public function findAll(): array;

    public function delete(Listing $listing): void;
}
