<?php

namespace OlxMonitor\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use OlxMonitor\Entity\UserFilter;

readonly class DoctrineUserFiltersRepository implements UserFiltersRepositoryInterface
{
    public function __construct(private Connection $connection)
    {}

    /**
     * @throws Exception
     */
    public function save(UserFilter $userFilter): void
    {
        $exists = $this->findByParam($userFilter->filterId, $userFilter->chatId);

        if ($exists === null) {
            $this->insert($userFilter);
        }
    }

    /**
     * @throws Exception
     */
    public function delete(UserFilter $userFilter): void
    {
        $sql = 'DELETE FROM user_filters WHERE chat_id = ?';
        $this->connection->executeStatement($sql, [$userFilter->chatId]);
    }

    /**
     * @throws Exception
     */
    public function findAllByFilterId(string $id): array
    {
        $sql = 'SELECT * FROM user_filters WHERE filter_id = ?';
        $results = $this->connection->fetchAllAssociative($sql, [$id]);

        return array_map([$this, 'hydrateUserFilter'], $results);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function findByParam(string $filterId, string $chatId): ?UserFilter
    {
        $sql = 'SELECT * FROM user_filters WHERE filter_id = ? AND chat_id = ?';
        $result = $this->connection->fetchOne($sql, [$filterId, $chatId]);

        return $result ? $this->hydrateUserFilter($result) : null;
    }

    /**
     * @throws Exception
     */
    private function insert(UserFilter $userFilter): void
    {
        $sql = 'INSERT INTO user_filters (filter_id, chat_id) VALUES (?, ?)';

        $this->connection->executeStatement($sql, [
            $userFilter->filterId,
            $userFilter->chatId,
        ]);
    }

    /**
     * @throws \Exception
     */
    private function hydrateUserFilter(array $data): UserFilter
    {
        return new UserFilter(
            $data['filter_id'],
            $data['chat_id'],
            new DateTimeImmutable($data['created_at']),
        );
    }
}
