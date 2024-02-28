<?php

namespace App\Repository;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class ItemRepository extends BaseRepository
{
    public function findNextNumero(): int
    {
        try {
            $numeroMax = (int) $this->getEntityManager()
                ->createQuery('SELECT MAX(i.numero) FROM App\Entity\Item i')
                ->getSingleScalarResult();
        } catch (NonUniqueResultException|NoResultException $e) {
            // LOG ?
            $numeroMax = 0;
        }

        return $numeroMax++;
    }
}
