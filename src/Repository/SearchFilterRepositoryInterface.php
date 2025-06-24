<?php

namespace OlxMonitor\Repository;

use OlxMonitor\Entity\SearchFilter;

interface SearchFilterRepositoryInterface
{
    public function save(SearchFilter $filter): void;

    public function findById(string $id): ?SearchFilter;

    public function findActive(): array;

    public function delete(SearchFilter $filter): void;
}
