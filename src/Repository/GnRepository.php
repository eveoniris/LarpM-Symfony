<?php

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\Personnage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
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
     * Trouve tous les gns actifs.
     *
     * @return ArrayCollection $gns
     */
    public function findActive(): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\Gn g WHERE g.actif = true ORDER BY g.date_debut ASC')
            ->getResult();
    }

    public function findPaginated(int $page, int $limit = 10, string $orderby = 'id', string $orderdir = 'ASC', $where = '1=1'): Paginator
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

    public function getPersonnages(Gn $gn): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')
            ->innerJoin('perso.gn', 'gn')
            ->where('gn.id = :gnid')
            ->setParameter('gnid', $gn->getId());
    }

    public function getParticipant(Gn $gn): QueryBuilder
    {
        /** @var ParticipantRepository $participantRepository */
        $participantRepository = $this->entityManager->getRepository(Personnage::class);

        return $participantRepository->createQueryBuilder('participant')
            ->innerJoin('participant.gn', 'gn')
            ->where('gn.id = :gnid')
            ->setParameter('gnid', $gn->getId());
    }
}
