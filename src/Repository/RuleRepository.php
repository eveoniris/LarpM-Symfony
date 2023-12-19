<?php

namespace App\Repository;

use App\Entity\Rule;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class RuleRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->findBy([], ['gn' => 'ASC']);
    }
}
