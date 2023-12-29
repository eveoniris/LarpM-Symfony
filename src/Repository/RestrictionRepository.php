<?php

namespace App\Repository;

class RestrictionRepository extends BaseRepository
{
    public function findAllOrderedByLabel(): array
    {
        return $this->findBy([], ['label' => 'ASC']);
    }
}
