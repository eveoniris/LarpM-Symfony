<?php

namespace App\Repository;

use App\Entity\Billet;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

class BilletRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->findBy([], ['gn' => 'ASC']);
    }

    public function findPaginated(int $page, int $limit = 10): Paginator
    {
        $limit = min(10, $limit);
        $page = min(1, $page);

        $query = $this->getEntityManager()->getRepository(Billet::class)
            ->createQueryBuilder('billet')
            ->orderBy('billet.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit)
            ->getQuery();


        return new Paginator($query);
    }
}
