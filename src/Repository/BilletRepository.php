<?php

declare(strict_types=1);

namespace App\Repository;

class BilletRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->findBy([], ['gn' => 'ASC']);
    }
}
