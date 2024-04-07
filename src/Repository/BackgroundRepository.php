<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\BackgroundRepository.
 *
 * @author kevin
 */
class BackgroundRepository extends BaseRepository
{
    /**
     * Trouve les background correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('b'));
        $qb->from(\App\Entity\Background::class, 'b');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findBackgrounds($gnId)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT b FROM App\Entity\Background b JOIN b.gn gn JOIN b.groupe g WHERE gn.id = ?1 ORDER BY g.numero ASC")
            ->setParameter(1, $gnId)
            ->getResult();
    }

    public function findList($type, $value, array $order = ['by' => 'titre', 'dir' => 'ASC'], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('b.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    protected function getQueryBuilder(?string $type, $value): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('b');
        $qb->from(\App\Entity\Background::class, 'b');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'auteur':
                    $qb->join('b.user', 'u');
                    $qb->andWhere('u.username LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'groupe':
                    $qb->join('b.groupe', 'g');
                    $qb->andWhere('g.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Type "%s" inconnu', $type));
            }
        }

        return $qb;
    }
}
