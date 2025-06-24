<?php

namespace OlxMonitor\Entity;

use DateTimeImmutable;

readonly class UserFilter
{
    public function __construct(
        public string $filterId,
        public int $chatId,
        public DateTimeImmutable $createdAt = new DateTimeImmutable(),
    ) {}
}
