<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Lignee;

class LigneeRepository extends BaseRepository
{
    /**
     * Trouve toutes les lignées.
     */
    /** @return list<Lignee> */
    public function findAll(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l');
        $qb->from(Lignee::class, 'l');
        $qb->orderBy('l.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche d'une liste de lignees.
     *
     * @param array<string, string> $order
     */
    public function findList(
        ?string $type,
        mixed $value,
        int $limit,
        int $offset,
        array $order = [],
    ): \Doctrine\ORM\Query {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l');
        $qb->from(Lignee::class, 'l');
        if ($type && $value) {
            switch ($type) {
                case 'id':
                    $qb->andWhere('l.id = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('l.nom LIKE :value');
                    $qb->setParameter('value', '%' . $value . '%');
                    break;
            }
        }

        $qb->setFirstResult((int) $offset);
        $qb->setMaxResults((int) $limit);
        $qb->orderBy('l.' . $order['by'], $order['dir']);

        return $qb->getQuery();
    }

    /**
     * Retourne le nombre de résultats correspondant à un critère.
     */
    public function findCount(string $type, ?string $value): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('l'));
        $qb->from(Lignee::class, 'l');

        if ($type && $value) {
            switch ($type) {
                case 'id':
                    $qb->andWhere('l.id = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('l.nom LIKE :value');
                    $qb->setParameter('value', '%' . $value . '%');
                    break;
            }
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
