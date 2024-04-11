<?php


namespace App\Repository;

use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\DebriefingRepository.
 *
 * @author kevin
 */
class DebriefingRepository extends BaseRepository
{
    /**
     * Trouve les debriefing correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('d'));
        $qb->from(\App\Entity\Debriefing::class, 'd');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findList(?string $type, $value, array $order = [], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value, $order);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('d.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    protected function getQueryBuilder(?string $type, $value, array $order = []): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d');
        $qb->from(\App\Entity\Debriefing::class, 'd');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'Auteur':
                    $qb->join('d.player', 'u');
                    $qb->andWhere('u.username LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'Scenariste':
                    $qb->join('d.user', 'u');
                    $qb->andWhere('u.username LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'Groupe':
                    $qb->join('d.groupe', 'g');
                    $qb->andWhere('g.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        return $qb;
    }
}
