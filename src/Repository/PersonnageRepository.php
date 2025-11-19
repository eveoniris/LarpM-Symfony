<?php

namespace App\Repository;

use App\Entity\Competence;
use App\Entity\Gn;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\PersonnageLignee;
use App\Entity\Titre;
use App\Entity\User;
use App\Service\OrderBy;
use Doctrine\DBAL\Result;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Deprecated;

class PersonnageRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->findBy([], ['nom' => 'ASC']);
    }

    public function findAllWithoutLangue(Gn $gn)
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                    SELECT p FROM App\Entity\Personnage p
                    INNER JOIN p.participants as pr
                    LEFT JOIN p.personnageLangues pl
                    WHERE pl.id IS NULL and pr.gn = :gnid
                DQL,
            );
        $query->setParameter('gnid', $gn->getId());

        return $query->getResult();
    }

    public function findAllVivant()
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                    SELECT p FROM App\Entity\Personnage p
                    WHERE p.vivant = 1
                DQL,
            );

        return $query->getResult();
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
        $qb->from(Participant::class, 'pa');
        $qb->join('pa.personnage', 'p');
        $qb->join('pa.groupeGn', 'gg');
        $qb->join('p.personnageBackground', 'pb');
        $qb->join('gg.gn', 'gn');
        $qb->andWhere('gn.id = :gnId')->setParameter('gnId', $gnId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find multiple personnage.
     */
    #[Deprecated]
    public function findByIds(array $ids)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('p');
        $qb->from(Personnage::class, 'p');
        $qb->andWhere('p.id IN (:ids)')->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    public function findCompetencesOrdered(Personnage $personnage)
    {
        /** @var CompetenceRepository $competenceRepository */
        $competenceRepository = $this->getEntityManager()->getRepository(Competence::class);
        $queryBuilder = $competenceRepository->getQueryBuilderFindAllOrderedByLabel()
            ->select('c')
            ->join('c.personnages', 'p')
            ->andWhere('p.id = :pid');

        $queryBuilder->setParameter('pid', $personnage->getId());

        return $queryBuilder->getQuery()->getResult();
    }

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

    private function buildSearchFromJoinWhereQuery(QueryBuilder $qb, array $criteria, ?string $orderBy = null): void
    {
        $qb->from(Personnage::class, 'p');
        // jointure sur le dernier participant créé (on se base sur le max id des personnage participants)
        $qb->leftjoin(
            'p.participants',
            'pa',
            'with',
            'pa.id =
 (SELECT MAX(pa2.id)
FROM App\Entity\Personnage p2
LEFT JOIN p2.participants pa2
 WHERE p2 = p
)',
        );
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

    public function findList(array $criteria = [], ?array $order = [], ?int $limit = null, ?int $offset = null)
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
                    // $qb->addOrderBy('p.genre', $orderDir);
                    break;
                default:
                    $qb->orderBy('p.'.$orderBy, $orderDir);
            }
        }

        return $qb->getQuery();
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

    public function getFindGenderQueryBuilder(int $genderId, ?int $ambigus = null): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($this->alias);
        $qb->from(Personnage::class, $this->alias);
        $qb = $this->gender($qb, $genderId, $ambigus);

        return $qb->orderBy($this->alias.'.nom', 'ASC');
    }

    public function gender(QueryBuilder $qb, int $gender, ?int $ambigus = null): QueryBuilder
    {
        if (!$ambigus) {
            $qb->andWhere($this->alias.'.genre = :value');
        } else {
            $qb->andWhere($this->alias.'.genre = :value OR '.$this->alias.'.genre = :ambigus');
            $qb->setParameter('ambigus', $ambigus);
        }

        return $qb->setParameter('value', $gender);
    }

    public function findAllElder(int $age = 60): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($this->alias);
        $qb->from(Personnage::class, $this->alias);
        $qb->andWhere($this->alias.'.age_reel >= :age');
        $qb->andWhere($this->alias.'.vivant = 1');
        $qb->setParameter('age', $age);

        return $qb->getQuery()->getResult();
    }

    public function vieillirAll(int $year = 5): Result
    {
        $connection = $this->getEntityManager()->getConnection();

        // On ajoute le nombre de nouvelles années et maj la tranche d'age
        $sql =
            <<<SQL
                UPDATE personnage p
                JOIN (
                    SELECT p.id,
                           p.age_reel + :addyear AS new_age,
                           a.id AS new_id_age
                    FROM personnage p
                    JOIN age a
                      ON a.minimumValue <= p.age_reel + :addyear
                    WHERE p.vivant = 1
                    AND NOT EXISTS (
                        SELECT 1
                          FROM age a2
                          WHERE a2.minimumValue <= p.age_reel + :addyear
                    AND a2.minimumValue > a.minimumValue
                      )
                ) sub ON p.id = sub.id
                SET p.age_reel = sub.new_age,
                    p.age_id   = sub.new_id_age;
            SQL;

        $statement = $connection->prepare($sql);
        $statement->bindValue('addyear', $year);

        return $statement->executeQuery();
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias.'.user', 'user');
       // $query->leftJoin('user.etatCivil', 'etatCivil');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias.'.nom',
            $alias.'.surnom',
       //     'user.username as username',
         //   'etatCivil.nom as user_nom',
         //   'etatCivil.prenom as user_prenom',
         //   'user.email as email',
          //  "CONCAT(etatCivil.nom, ' ', etatCivil.prenom) AS HIDDEN nomPrenom",
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias.'.nom' => [
                OrderBy::ASC => [$alias.'.nom' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.nom' => OrderBy::DESC],
            ],
           /* 'email' => [
                OrderBy::ASC => ['email' => OrderBy::ASC],
                OrderBy::DESC => ['email' => OrderBy::DESC],
            ],
            'username' => [
                OrderBy::ASC => ['username' => OrderBy::ASC],
                OrderBy::DESC => ['username' => OrderBy::DESC],
            ],*/
          /*  'etatCivil.nom' => [
                OrderBy::ASC => ['etatCivil.nom' => OrderBy::ASC],
                OrderBy::DESC => ['etatCivil.nom' => OrderBy::DESC],
            ],
            'etatCivil.prenom' => [
                OrderBy::ASC => ['etatCivil.prenom' => OrderBy::ASC],
                OrderBy::DESC => ['etatCivil.prenom' => OrderBy::DESC],
            ],
            'nomPrenom' => [
                OrderBy::ASC => ['nomPrenom' => OrderBy::ASC],
                OrderBy::DESC => ['nomPrenom' => OrderBy::DESC],
            ],*/
        ];
    }

    public function translateAttributes(): array
    {
        return [
            'email' => $this->translator->trans('Email', domain: 'repository'),
            'etatCivil.nom' => $this->translator->trans('Nom joueur', domain: 'repository'),
            'etatCivil.prenom' => $this->translator->trans('Prenom', domain: 'repository'),
            'username' => $this->translator->trans('Pseudo', domain: 'repository'),
            'pseudo' => $this->translator->trans('Surnom', domain: 'repository'),
            'nom' => $this->translator->trans('Nom', domain: 'repository'),
            'prenom' => $this->translator->trans('Prenom', domain: 'repository'),
            'nomPrenom',
            'HIDDEN nomPrenom' => $this->translator->trans('Nom prénom', domain: 'repository'),
        ];
    }

    public function user(QueryBuilder $query, User $user): QueryBuilder
    {
        $query->andWhere($this->alias.'.user = :value');

        return $query->setParameter('value', $user->getId());
    }

    /**
     * Count alive personnage per user
     */
    public function countUser(User $user): int
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                    SELECT COUNT(p) FROM App\Entity\Personnage p
                    INNER JOIN p.user u
                    WHERE u.id = :uid AND p.vivant = 1
                DQL,
            );
        $query->setParameter('uid', $user->getId());

        return (int) $query->getSingleScalarResult();
    }
}
