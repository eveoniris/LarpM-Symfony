<?php


namespace App\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\PotionRepository.
 *
 * @author kevin
 */
class PotionRepository extends BaseRepository
{
    /**
     * Trouve toute les potions en fonction de leur niveau.
     *
     * @return ArrayCollection $sorts
     */
    public function findByNiveau($niveau)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM App\Entity\Potion p Where p.niveau = ?1 and (p.secret = false or p.secret is null) ORDER BY p.label ASC')
            ->setParameter(1, $niveau)
            ->getResult();
    }

    /**
     * Trouve le nombre de potions correspondant aux critères de recherche.
     */
    public function findCount(?string $type, $value)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->select($qb->expr()->count('p'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les potions correspondant aux critères de recherche.
     */
    public function findList(?string $type, $value, array $order = [], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($type, $value);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('p.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    protected function getQueryBuilder(?string $type, $value): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p');
        $qb->from(\App\Entity\Potion::class, 'p');

        // retire les caractères non imprimable d'une chaine UTF-8
        $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string) $value));

        if ($type && $value) {
            switch ($type) {
                case 'label':
                    $qb->andWhere('p.label LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'description':
                    $qb->andWhere('p.description LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
                case 'numero':
                    $qb->andWhere('p.numero = :value');
                    $qb->setParameter('value', (int) $value);
                    break;
                case 'id':
                    $qb->andWhere('p.id = :value');
                    $qb->setParameter('value', (int) $value);
                    break;
            }
        }

        return $qb;
    }
}
