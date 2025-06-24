<?php

declare(strict_types=1);

namespace OlxMonitor\Entity;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

readonly class Listing
{
    public function __construct(
        public UuidInterface $id,
        public int $externalId,
        public string $filterId,
        public string $title,
        public string $description,
        public float $price,
        public string $currency,
        public string $url,
        public string $location,
        public array $images,
        public DateTimeImmutable $publishedAt,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt = null,
        public bool $isActive = true,
    ) {}

    public function withPrice(float $newPrice): self
    {
        return new self(
            $this->id,
            $this->externalId,
            $this->filterId,
            $this->title,
            $this->description,
            $newPrice,
            $this->currency,
            $this->url,
            $this->location,
            $this->images,
            $this->publishedAt,
            $this->createdAt,
            new DateTimeImmutable(),
            $this->isActive
        );
    }

    public function markAsInactive(): self
    {
        return new self(
            $this->id,
            $this->externalId,
            $this->filterId,
            $this->title,
            $this->description,
            $this->price,
            $this->currency,
            $this->url,
            $this->location,
            $this->images,
            $this->publishedAt,
            $this->createdAt,
            new DateTimeImmutable(),
            false,
        );
    }
}
