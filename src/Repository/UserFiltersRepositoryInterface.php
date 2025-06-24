<?php

namespace OlxMonitor\Repository;

use OlxMonitor\Entity\UserFilter;

interface UserFiltersRepositoryInterface
{
    public function save(UserFilter $userFilter): void;

    public function delete(UserFilter $userFilter): void;

    /**
     * @return UserFilter[]
     */
    public function findAllByFilterId(string $id): array;

    public function findByParam(string $filterId, string $chatId): ?UserFilter;
}
