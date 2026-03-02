<?php

declare(strict_types=1);

namespace App\Repository;

class RuleRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->findBy([], ['gn' => 'ASC']);
    }
}
