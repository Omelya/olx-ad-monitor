<?php

namespace OlxMonitor\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use OlxMonitor\Entity\PriceHistory;
use Ramsey\Uuid\Uuid;

readonly class DoctrinePriceHistoryRepository implements PriceHistoryRepositoryInterface
{
    public function __construct(private Connection $connection) {}

    /**
     * @throws Exception
     */
    public function save(PriceHistory $priceHistory): void
    {
        $existingHistory = $this->findById($priceHistory->id->toString());

        if ($existingHistory === null) {
            $this->insert($priceHistory);
        } else {
            $this->update($priceHistory);
        }
    }

    /**
     * @throws Exception
     */
    public function findByListingId(string $listingId): array
    {
        $sql = 'SELECT * FROM price_history WHERE listing_id = ? ORDER BY changed_at ASC';
        $results = $this->connection->fetchAllAssociative($sql, [$listingId]);

        return array_map([$this, 'hydratePriceHistory'], $results);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findById(string $id): ?PriceHistory
    {
        $sql = 'SELECT * FROM price_history WHERE id = ?';
        $data = $this->connection->fetchAssociative($sql, [$id]);

        return $data ? $this->hydratePriceHistory($data) : null;
    }

    /**
     * @throws Exception
     */
    public function delete(PriceHistory $priceHistory): void
    {
        $sql = 'DELETE FROM price_history WHERE id = ?';
        $this->connection->executeStatement($sql, [$priceHistory->id->toString()]);
    }

    /**
     * @throws Exception
     */
    private function insert(PriceHistory $priceHistory): void
    {
        $sql = '
            INSERT INTO price_history (id, listing_id, old_price, new_price, changed_at)
            VALUES (?, ?, ?, ?, ?)
        ';

        $this->connection->executeStatement($sql, [
            $priceHistory->id->toString(),
            $priceHistory->listingId->toString(),
            $priceHistory->oldPrice,
            $priceHistory->newPrice,
            $priceHistory->changedAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @throws Exception
     */
    private function update(PriceHistory $priceHistory): void
    {
        $sql = '
            UPDATE price_history SET
                listing_id = ?, old_price = ?, new_price = ?, changed_at = ?
            WHERE id = ?
        ';

        $this->connection->executeStatement($sql, [
            $priceHistory->listingId->toString(),
            $priceHistory->oldPrice,
            $priceHistory->newPrice,
            $priceHistory->changedAt->format('Y-m-d H:i:s'),
            $priceHistory->id->toString(),
        ]);
    }

    /**
     * @throws \Exception
     */
    private function hydratePriceHistory(array $data): PriceHistory
    {
        return new PriceHistory(
            id: Uuid::fromString($data['id']),
            listingId: Uuid::fromString($data['listing_id']),
            oldPrice: (float) $data['old_price'],
            newPrice: (float) $data['new_price'],
            changedAt: new DateTimeImmutable($data['changed_at']),
        );
    }
}
