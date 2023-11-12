<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class BilletRepository extends EntityRepository
{
    public function findAll(): array
    {
        return $this->findBy([], ['gn' => 'ASC']);
    }
}
