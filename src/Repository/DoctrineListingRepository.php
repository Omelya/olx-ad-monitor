<?php

namespace OlxMonitor\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use OlxMonitor\Entity\Listing;
use Ramsey\Uuid\Uuid;

readonly class DoctrineListingRepository implements ListingRepositoryInterface
{
    public function __construct(private Connection $connection)
    {}

    /**
     * @throws \JsonException
     * @throws Exception
     */
    public function save(Listing $listing): void
    {
        $existingListing = $this->findByExternalId($listing->externalId);

        if ($existingListing === null) {
            $this->insert($listing);
        } else {
            $this->update($listing);
        }
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    public function findByExternalId(string $externalId): ?Listing
    {
        $sql = 'SELECT * FROM listings WHERE external_id = ?';
        $data = $this->connection->fetchAssociative($sql, [$externalId]);

        return $data ? $this->hydrateListing($data) : null;
    }

    /**
     * @throws Exception
     */
    public function findActiveByFilterId(string $filterId): array
    {
        $sql = 'SELECT * FROM listings WHERE filter_id = ? AND is_active = 1';
        $results = $this->connection->fetchAllAssociative($sql, [$filterId]);

        return array_map([$this, 'hydrateListing'], $results);
    }

    /**
     * @throws Exception
     */
    public function findAll(): array
    {
        $sql = 'SELECT * FROM listings ORDER BY created_at DESC';
        $results = $this->connection->fetchAllAssociative($sql);

        return array_map([$this, 'hydrateListing'], $results);
    }

    /**
     * @throws Exception
     */
    public function delete(Listing $listing): void
    {
        $sql = 'DELETE FROM listings WHERE id = ?';
        $this->connection->executeStatement($sql, [$listing->id->toString()]);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    private function insert(Listing $listing): void
    {
        $sql = '
            INSERT INTO listings (
                id, external_id, filter_id, title, description, price, currency, url, location,
                images, published_at, created_at, updated_at, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ';

        $this->connection->executeStatement($sql, [
            $listing->id->toString(),
            $listing->externalId,
            $listing->filterId,
            $listing->title,
            $listing->description,
            $listing->price,
            $listing->currency,
            $listing->url,
            $listing->location,
            json_encode($listing->images, JSON_THROW_ON_ERROR),
            $listing->publishedAt->format('Y-m-d H:i:s'),
            $listing->createdAt->format('Y-m-d H:i:s'),
            $listing->updatedAt?->format('Y-m-d H:i:s'),
            $listing->isActive ? 1 : 0,
        ]);
    }

    /**
     * @throws Exception
     * @throws \JsonException
     */
    private function update(Listing $listing): void
    {
        $sql = '
            UPDATE listings SET
                title = ?, description = ?, price = ?, currency = ?, url = ?,
                location = ?, images = ?, updated_at = ?, is_active = ?
            WHERE external_id = ?
        ';

        $this->connection->executeStatement($sql, [
            $listing->title,
            $listing->description,
            $listing->price,
            $listing->currency,
            $listing->url,
            $listing->location,
            json_encode($listing->images, JSON_THROW_ON_ERROR),
            $listing->updatedAt?->format('Y-m-d H:i:s'),
            $listing->isActive ? 1 : 0,
            $listing->externalId,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    private function hydrateListing(array $data): Listing
    {
        $images = json_decode($data['images'], true, 512, JSON_THROW_ON_ERROR) ?? [];

        return new Listing(
            id: Uuid::fromString($data['id']),
            externalId: $data['external_id'],
            filterId: $data['filter_id'],
            title: $data['title'],
            description: $data['description'],
            price: (float) $data['price'],
            currency: $data['currency'],
            url: $data['url'],
            location: $data['location'],
            images: $images,
            publishedAt: new DateTimeImmutable($data['published_at']),
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: $data['updated_at'] ? new DateTimeImmutable($data['updated_at']) : null,
            isActive: (bool) $data['is_active'],
        );
    }
}
