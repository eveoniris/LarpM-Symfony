<?php

namespace App\Repository;

use App\Entity\Gn;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\Pagination\Paginator;

class GnRepository extends BaseRepository
{
    /**
     * Recherche le prochain GN (le plus proche de la date du jour).
     */
    public function findNext()
    {
        // AND g.date_debut > CURRENT_DATE()
        $gns = $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut DESC')
            ->getResult();

        return $gns[0] ?? null;
    }

    /**
     * Classe les gn par date (du plus proche au plus lointain).
     */
    public function findAll(): array
    {
        return $this->findBy([], ['date_debut' => 'DESC']);
    }

    /**
     * Trouve les gns correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = []): float|bool|int|string|null
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('g'));
        $qb->from(\App\Entity\Gn::class, 'g');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve tous les gns actifs.
     *
     * @return ArrayCollection $gns
     */
    public function findActive(): ArrayCollection
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut ASC')
            ->getResult();
    }

    public function findPaginated(int $page, int $limit = 10, string $orderby = 'id', string $orderdir = 'ASC'): Paginator
    {
        $limit = abs($limit);

        $result = [];

        /*
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('c', 'p')
            ->from('App\Entity\Products', 'p')
            ->join('p.categories', 'c')
            ->where("c.slug = '$slug'")
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);
        */

        $query = $this->getEntityManager()->getRepository(Gn::class)
            ->createQueryBuilder('gn')
            ->orderBy('gn.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit)
            ->getQuery();

        $paginator = new Paginator($query);

        return $paginator;
        $data = $paginator->getQuery()->getResult();

        return $data;
        /*
        // On vérifie qu'on a des données
        if (empty($data)) {
            return $result;
        }

        // On calcule le nombre de pages
        $pages = ceil($paginator->count() / $limit);

        // On remplit le tableau
        $result['data'] = $data;
        $result['pages'] = $pages;
        $result['page'] = $page;
        $result['limit'] = $limit;

        return $result;*/
    }
}
