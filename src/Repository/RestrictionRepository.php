<?php

declare(strict_types=1);

namespace App\Repository;

class RestrictionRepository extends BaseRepository
{
    /** @return array<int, \App\Entity\Restriction> */
    public function findAllOrderedByLabel(): array
    {
        return $this->findBy([], ['label' => 'ASC']);
    }
}
