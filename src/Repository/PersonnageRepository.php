<?php


namespace App\Repository;

use App\Entity\PersonnageLignee;
use App\Entity\Titre;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Deprecated;

class PersonnageRepository extends BaseRepository
{
    /**
     * Trouve le nombre de personnages correspondant aux critères de recherche.
     */
    #[Deprecated]
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('distinct p'));
        $this->buildSearchFromJoinWhereQuery($qb, $criteria);

        return $qb->getQuery()->getSingleScalarResult();
    }


    public function findList(array $criteria = [], ?array $order = [], int $limit = null, int $offset = null)
    {
        $orderBy = '';
        $orderDir = 'ASC';
        if ($order && array_key_exists('by', $order)) {
            $orderBy = $order['by'];
            if (array_key_exists('dir', $order)) {
                $orderDir = $order['dir'];
            }
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct p');
        $this->buildSearchFromJoinWhereQuery($qb, $criteria, $orderBy);

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if (!empty($orderBy)) {
            switch ($orderBy) {
                case 'groupe':
                    $qb->addOrderBy('gr.nom', $orderDir);
                    break;
                case 'classe':
                    $qb->addOrderBy('cl.label_feminin', $orderDir);
                    $qb->addOrderBy('cl.label_masculin', $orderDir);
                    //$qb->addOrderBy('p.genre', $orderDir);
                    break;
                default:
                    $qb->orderBy('p.'.$orderBy, $orderDir);
            }
        }

        return $qb->getQuery();
    }

    private function buildSearchFromJoinWhereQuery(QueryBuilder $qb, array $criteria, string $orderBy = null): void
    {
        $qb->from(\App\Entity\Personnage::class, 'p');
        // jointure sur le dernier participant créé (on se base sur le max id des personnage participants)
        $qb->join('p.participants', 'pa', 'with', 'pa.id =
 (SELECT MAX(pa2.id) 
FROM App\Entity\Personnage p2 
LEFT JOIN p2.participants pa2
 WHERE p2 = p
)');
        if (array_key_exists('religion', $criteria)) {
            $qb->join('p.personnagesReligions', 'ppr');
            $qb->join('ppr.religion', 'pr');
        }

        if (array_key_exists('classe', $criteria) || (null !== $orderBy && '' !== $orderBy && 'classe' == $orderBy)) {
            $qb->join('p.classe', 'cl');
        }

        if (array_key_exists('competence', $criteria)) {
            $qb->join('p.competences', 'cmp');
        }

        if (array_key_exists('groupe', $criteria) || (null !== $orderBy && '' !== $orderBy && 'groupe' == $orderBy)) {
            // on rajoute la jointure sur le groupeGn -> groupe à partir du dernier participant
            $qb->join('pa.groupeGn', 'grgn');
            $qb->join('grgn.groupe', 'gr');
        }

        // ajoute les conditions
        if (array_key_exists('classe', $criteria)) {
            $qb->andWhere('cl.id = :classeId')->setParameter('classeId', $criteria['classe']);
        }

        if (array_key_exists('competence', $criteria)) {
            $qb->andWhere('cmp.id = :competenceId')->setParameter('competenceId', $criteria['competence']);
        }

        if (array_key_exists('groupe', $criteria)) {
            $qb->andWhere('gr.id = :groupeId')->setParameter('groupeId', $criteria['groupe']);
        }

        if (array_key_exists('religion', $criteria)) {
            $qb->andWhere('pr.id = :religionId')->setParameter('religionId', $criteria['religion']);
        }

        if (array_key_exists('id', $criteria)) {
            $qb->andWhere('p.id = :personnageId')->setParameter('personnageId', $criteria['id']);
        }

        if (array_key_exists('nom', $criteria)) {
            $qb->andWhere('p.nom LIKE :nom OR p.surnom LIKE :nom')->setParameter('nom', '%'.$criteria['nom'].'%');
        }
    }

    /**
     * Find multiple personnage.
     */
    #[Deprecated]
    public function findByIds(array $ids)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p');
        $qb->from(\App\Entity\Personnage::class, 'p');
        $qb->andWhere('p.id IN (:ids)')->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve tous les backgrounds liés aux personnages.
     *
     * @param int $gnId
     */
    public function findBackgrounds($gnId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('pb');
        $qb->from(\App\Entity\Participant::class, 'pa');
        $qb->join('pa.personnage', 'p');
        $qb->join('pa.groupeGn', 'gg');
        $qb->join('p.personnageBackground', 'pb');
        $qb->join('gg.gn', 'gn');
        $qb->andWhere('gn.id = :gnId')->setParameter('gnId', $gnId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Fourni la liste des descendants directs.
     */
    public function findDescendants($personnage_id)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d');
        $qb->from(PersonnageLignee::class, 'd');
        $qb->join('d.personnage', 'p');
        $qb->where('(d.parent1 = :parent OR d.parent2 = :parent)');
        $qb->orderBy('d.personnage', 'DESC');
        $qb->setParameter('parent', $personnage_id);

        return $qb->getQuery()->getResult();
    }

    /**
     * Fourni la liste des descendants directs.
     */
    public function findTitre($renommee)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('t');
        $qb->from(Titre::class, 't');
        $qb->where('t.renomme >= :renommee');
        $qb->orderBy('t.renomme', 'ASC');
        $qb->setParameter('renommee', $renommee);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getResult();
    }

}
