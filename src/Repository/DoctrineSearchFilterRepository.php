<?php

namespace OlxMonitor\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use OlxMonitor\Entity\SearchFilter;
use Ramsey\Uuid\Uuid;

readonly class DoctrineSearchFilterRepository implements SearchFilterRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function save(SearchFilter $filter): void
    {
        $existingFilter = $this->findById($filter->id->toString());

        if ($existingFilter === null) {
            $this->insert($filter);
        } else {
            $this->update($filter);
        }
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findById(string $id): ?SearchFilter
    {
        $sql = 'SELECT * FROM search_filters WHERE id = ?';
        $data = $this->connection->fetchAssociative($sql, [$id]);

        return $data ? $this->hydrateFilter($data) : null;
    }

    /**
     * @throws Exception
     */
    public function findActive(): array
    {
        $sql = 'SELECT * FROM search_filters WHERE is_active = 1';
        $results = $this->connection->fetchAllAssociative($sql);

        return array_map([$this, 'hydrateFilter'], $results);
    }

    /**
     * @throws Exception
     */
    public function delete(SearchFilter $filter): void
    {
        $sql = 'DELETE FROM search_filters WHERE id = ?';
        $this->connection->executeStatement($sql, [$filter->id->toString()]);
    }

    /**
     * @throws \JsonException
     * @throws Exception
     */
    private function insert(SearchFilter $filter): void
    {
        $sql = '
            INSERT INTO search_filters (
                id, name, category, subcategory, type, filters, is_active, last_checked, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ';

        $this->connection->executeStatement($sql, [
            $filter->id->toString(),
            $filter->name,
            $filter->category,
            $filter->subcategory,
            $filter->type,
            json_encode($filter->filters, JSON_THROW_ON_ERROR),
            $filter->isActive ? 1 : 0,
            $filter->lastChecked?->format('Y-m-d H:i:s'),
            $filter->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    private function update(SearchFilter $filter): void
    {
        $sql = '
            UPDATE search_filters SET
                name = ?, category = ?, subcategory = ?, type = ?, filters = ?,
                is_active = ?, last_checked = ?
            WHERE id = ?
        ';

        $this->connection->executeStatement($sql, [
            $filter->name,
            $filter->category,
            $filter->subcategory,
            $filter->type,
            json_encode($filter->filters, JSON_THROW_ON_ERROR),
            $filter->isActive ? 1 : 0,
            $filter->lastChecked?->format('Y-m-d H:i:s'),
            $filter->id->toString(),
        ]);
    }

    /**
     * @throws \Exception
     */
    private function hydrateFilter(array $data): SearchFilter
    {
        $filters = json_decode($data['filters'], true, 512, JSON_THROW_ON_ERROR) ?? [];

        return new SearchFilter(
            id: Uuid::fromString($data['id']),
            name: $data['name'],
            category: $data['category'],
            subcategory: $data['subcategory'],
            type: $data['type'],
            filters: $filters,
            isActive: (bool) $data['is_active'],
            lastChecked: $data['last_checked'] ? new DateTimeImmutable($data['last_checked']) : null,
            createdAt: new DateTimeImmutable($data['created_at']),
        );
    }
}