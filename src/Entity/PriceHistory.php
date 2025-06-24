<?php

namespace OlxMonitor\Entity;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

readonly class PriceHistory
{
    public function __construct(
        public UuidInterface $id,
        public UuidInterface $listingId,
        public float $oldPrice,
        public float $newPrice,
        public DateTimeImmutable $changedAt,
    ) {}
}
