<?php

namespace OlxMonitor\Entity;

use DateTimeImmutable;
use Ramsey\Uuid\UuidInterface;

readonly class SearchFilter
{
    public function __construct(
        public UuidInterface $id,
        public string $name,
        public string $category,
        public string $subcategory,
        public string $type,
        public array $filters,
        public bool $isActive = true,
        public ?DateTimeImmutable $lastChecked = null,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {}

    public function withLastChecked(DateTimeImmutable $lastChecked): self
    {
        return new self(
            $this->id,
            $this->name,
            $this->category,
            $this->subcategory,
            $this->type,
            $this->filters,
            $this->isActive,
            $lastChecked,
            $this->createdAt
        );
    }
}
