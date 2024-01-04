<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\ItemRepository.
 *
 * @author kevin
 */
class ItemRepository extends BaseRepository
{
    /**
     * Recherche le prochain GN (le plus proche de la date du jour).
     */
    public function findNextNumero()
    {
        $numeroMax = $this->getEntityManager()
            ->createQuery('SELECT MAX(i.numero) FROM App\Entity\Item i')
            ->getSingleScalarResult();

        return $numeroMax++;
    }

    public function findAll(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i');
        $qb->from(\App\Entity\Item::class, 'i');

        $qb->orderBy('i.numero', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
