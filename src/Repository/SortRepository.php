<?php


namespace App\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\SortRepository.
 *
 * @author kevin
 */
class SortRepository extends BaseRepository
{
    /**
     * Find all Apprenti sorts ordered by label.
     *
     * @return ArrayCollection $sorts
     */
    public function findByNiveau($niveau)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT s FROM App\Entity\Sort s Where s.niveau = ?1 AND (s.secret = 0 OR s.secret IS NULL) ORDER BY s.label ASC')
            ->setParameter(1, $niveau)
            ->getResult();
    }

    /**
     * Trouve le nombre de sorts correspondant aux critères de recherche.
     */
    public function findCount(?string $type, $value)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->select($qb->expr()->count('s'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les sorts correspondant aux critères de recherche.
     */
    public function findList(?string $type, $value, array $order = [], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('s.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    protected function getQueryBuilder(?string $type, $value): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('s');
        $qb->from(\App\Entity\Sort::class, 's');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'label':
                    $qb->andWhere('s.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'domaine':
                    $qb->join('s.domaine', 'd');
                    $qb->andWhere('d.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'description':
                    $qb->andWhere('s.description LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'id':
                    $qb->andWhere('s.id = :value');
                    $qb->setParameter('value', (int) $value);
                    break;
            }
        }

        return $qb;
    }
}
