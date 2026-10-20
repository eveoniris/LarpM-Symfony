<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\IntrigueHasGroupeSecondaire;
use App\Entity\Membre;
use App\Entity\Participant;
use App\Entity\Personnage;
use App\Entity\Postulant;
use App\Entity\SecondaryGroup;
use App\Entity\User;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;
use SensitiveParameter;

class SecondaryGroupRepository extends BaseRepository
{
    /**
     * Trouve tous les groupes secondaire publics.
     *
     * @return list<SecondaryGroup>
     */
    public function findAllPublic(): array
    {
        return $this->getEntityManager()->createQuery('SELECT g FROM App\Entity\SecondaryGroup g WHERE g.secret = false or g.secret is null')->getResult();
    }

    /**
     * Compte les groupes secondaires correspondants aux critères de recherche.
     */
    /** @param array<string, mixed> $criteria */
    public function findCount(array $criteria = []): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('g'));
        $qb->from(SecondaryGroup::class, 'g');

        foreach ($criteria as $critere) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $critere);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les groupes secondaires correspondants aux critères de recherche.
     */
    /**
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $order
     */
    public function findList(int $limit, int $offset, array $criteria = [], array $order = []): \Doctrine\ORM\Query
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct g');
        $qb->from(SecondaryGroup::class, 'g');

        foreach ($criteria as $critere) {
            $qb->andWhere($critere);
        }

        $qb->setFirstResult((int) $offset);
        $qb->setMaxResults((int) $limit);
        $qb->orderBy('g.' . $order['by'], $order['dir']);

        return $qb->getQuery();
    }

    public function getPersonnages(SecondaryGroup $secondaryGroup): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')->innerJoin('perso.secondaryGroups', 'sg')->where('sg.id = :sgid')->setParameter('sgid', $secondaryGroup->getId());
    }

    // TODO inner join personnage from member
    public function isMember(
        SecondaryGroup $secondaryGroup,
        ?Membre $membre = null,
        ?Personnage $personnage = null,
    ): bool {
        $query = $this->getEntityManager()->createQuery(<<<DQL
            SELECT max(m.id) FROM App\Entity\membre m
            WHERE (m.personnage = :pid OR m.id = :mid) AND m.secondaryGroup = :sgid
            DQL);

        return (bool) $query->setParameter('pid', $personnage?->getId())->setParameter('mid', $membre?->getId())->setParameter('sgid', $secondaryGroup->getId())->getSingleScalarResult();
    }

    /** @param string|array<int|string, string|array<string, mixed>|null>|null $attributes */
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $orderBy ??= $this->orderBy;
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias . '.secondaryGroupType', 'sgtype');
        $query->select($this->alias, 'sgtype');

        if ('secret' === $attributes) {
            $orderBy->addOrderToQuery($query);

            return $this->secret($query, filter_var($search, \FILTER_VALIDATE_BOOLEAN));
        }

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function secret(QueryBuilder $query, #[SensitiveParameter] bool $secret): QueryBuilder
    {
        if (!$secret) {
            $query->andWhere($this->alias . '.secret = :value OR ' . $this->alias . '.secret IS NULL');
        } else {
            $query->andWhere($this->alias . '.secret = :value');
        }

        return $query->setParameter('value', $secret);
    }

    /** @return array<int, string> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.label', // => 'Libellé',
            $alias . '.description', // => 'Description',
            $alias . '.description_secrete',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias . '.label' => [
                OrderBy::ASC => [$alias . '.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.label' => OrderBy::DESC],
            ],
            $alias . '.secret' => [
                OrderBy::ASC => [$alias . '.secret' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.secret' => OrderBy::DESC],
            ],
            $alias . '.description' => [
                OrderBy::ASC => [$alias . '.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description' => OrderBy::DESC],
            ],
            $alias . '.description_secrete' => [
                OrderBy::ASC => [$alias . '.description_secrete' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description_secrete' => OrderBy::DESC],
            ],
        ];
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description_secrete' => $this->translator->trans('Description secrète', domain: 'repository'),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'label' => $this->translator->trans('Nom', domain: 'repository'),
            'secret' => $this->translator->trans('Secret', domain: 'repository'),
        ];
    }

    public function userCanSeeSecret(User $user, SecondaryGroup $secondaryGroup): bool
    {
        $query = $this->getEntityManager()->createQuery(<<<DQL
            SELECT MAX(sg) as exists
            FROM App\Entity\User u
            INNER JOIN u.personnages as p
            INNER JOIN p.secondaryGroups as sg
            INNER JOIN sg.membres as m
            WHERE u.id = :uid AND sg.id = :sgid AND m.secret = 1
            DQL);

        return (bool) $query->setParameter('uid', $user->getId())->setParameter('sgid', $secondaryGroup->getId())->getSingleScalarResult();
    }

    public function userIsGroupLeader(User $user, SecondaryGroup $secondaryGroup): bool
    {
        $query = $this->getEntityManager()->createQuery(<<<DQL
            SELECT MAX(sg) as exists
            FROM App\Entity\User u
            INNER JOIN u.personnages as p
            INNER JOIN p.secondaryGroups as sg
            WHERE u.id = :uid AND sg.id = :sgid
            DQL);

        return (bool) $query->setParameter('uid', $user->getId())->setParameter('sgid', $secondaryGroup->getId())->getSingleScalarResult();
    }

    /** @return list<int> */
    public function getMembershipGroupIds(int $personnageId): array
    {
        $alias = $this->getAlias();
        $qb = $this->createQueryBuilder($alias);
        $result = $qb
            ->select("$alias.id")
            ->leftJoin("$alias.membres", 'm')
            ->where($qb->expr()->orX($qb->expr()->eq("$alias.personnage", ':pid'), $qb->expr()->eq('m.personnage', ':pid')))
            ->setParameter('pid', $personnageId)
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'id');
    }

    /** @return list<int> */
    public function getPostulantGroupIds(int $personnageId): array
    {
        $alias = $this->getAlias();
        $qb = $this->createQueryBuilder($alias);
        $result = $qb
            ->select("$alias.id")
            ->leftJoin("$alias.postulants", 'p')
            ->where($qb->expr()->eq('p.personnage', ':pid'))
            ->setParameter('pid', $personnageId)
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'id');
    }

    public function visibleForPersonnage(QueryBuilder $queryBuilder, int $personnagesId): QueryBuilder
    {
        return $queryBuilder
            ->leftjoin($this->alias . '.membres', 'm')
            ->orWhere($this->alias . '.secret = 1 AND (' . $this->alias . '.personnage = :personnageId OR m.personnage = :personnageId)')
            ->orWhere($this->alias . '.secret IS NULL OR ' . $this->alias . '.secret = false')
            ->setParameter('personnageId', $personnagesId);
    }

    /** @return array<int, array<string, mixed>> */
    public function visibleForUser(User $user): array
    {
        $query = $this->getEntityManager()->createQuery(<<<DQL
            SELECT DISTINCT sg
            FROM App\Entity\User u
            INNER JOIN App\Entity\Personnage p
            LEFT JOIN App\Entity\SecondaryGroup sg
            LEFT JOIN App\Entity\membre m
            WHERE u.id = :uid AND (sg.id IS NOT NULL OR m.id IS NOT NULL)
            DQL);

        return $query->setParameter('uid', $user->getId())->getScalarResult();
    }

    /**
     * Charge le groupe et sa collection de membres (personnage et joueur compris) en une requête.
     * La collection `membres` du groupe est initialisée : les lectures suivantes ne coûtent plus rien.
     *
     * @param bool $withReligions joint aussi les religions de chaque personnage
     */
    public function findWithMembres(int $id, bool $withReligions = false): ?SecondaryGroup
    {
        $qb = $this
            ->createQueryBuilder('g')
            ->addSelect('m', 'p', 'u')
            ->leftJoin('g.membres', 'm')
            ->leftJoin('m.personnage', 'p')
            ->leftJoin('p.user', 'u')
            ->where('g.id = :id')
            ->setParameter('id', $id);

        if ($withReligions) {
            $qb
                ->addSelect('pr', 'r', 'rl')
                ->leftJoin('p.personnagesReligions', 'pr')
                ->leftJoin('pr.religion', 'r')
                ->leftJoin('pr.religionLevel', 'rl');
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function countMembres(SecondaryGroup $secondaryGroup): int
    {
        return (int) $this
            ->getEntityManager()
            ->createQuery('SELECT COUNT(m.id) FROM App\Entity\Membre m WHERE m.secondaryGroup = :g')
            ->setParameter('g', $secondaryGroup->getId())
            ->getSingleScalarResult();
    }

    public function countPostulants(SecondaryGroup $secondaryGroup): int
    {
        return (int) $this
            ->getEntityManager()
            ->createQuery('SELECT COUNT(po.id) FROM App\Entity\Postulant po WHERE po.secondaryGroup = :g')
            ->setParameter('g', $secondaryGroup->getId())
            ->getSingleScalarResult();
    }

    public function hasPostulant(SecondaryGroup $secondaryGroup, Personnage $personnage): bool
    {
        return (
            (int) $this
                ->getEntityManager()
                ->createQuery('SELECT COUNT(po.id) FROM App\Entity\Postulant po WHERE po.secondaryGroup = :g AND po.personnage = :p')
                ->setParameter('g', $secondaryGroup->getId())
                ->setParameter('p', $personnage->getId())
                ->getSingleScalarResult() > 0
        );
    }

    public function hasMembre(SecondaryGroup $secondaryGroup, Personnage $personnage): bool
    {
        return (
            (int) $this
                ->getEntityManager()
                ->createQuery('SELECT COUNT(m.id) FROM App\Entity\Membre m WHERE m.secondaryGroup = :g AND m.personnage = :p')
                ->setParameter('g', $secondaryGroup->getId())
                ->setParameter('p', $personnage->getId())
                ->getSingleScalarResult() > 0
        );
    }

    /**
     * Postulants avec personnage, joueur et groupe de jeu (et religions si demandé) en une requête.
     *
     * @return list<Postulant>
     */
    public function findPostulants(SecondaryGroup $secondaryGroup, bool $withReligions = false): array
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('po', 'p', 'u', 'pg')
            ->from(Postulant::class, 'po')
            ->join('po.personnage', 'p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.groupe', 'pg')
            ->where('po.secondaryGroup = :g')
            ->orderBy('po.date', 'ASC')
            ->addOrderBy('po.id', 'ASC')
            ->setParameter('g', $secondaryGroup->getId());

        if ($withReligions) {
            $qb
                ->addSelect('pr', 'r', 'rl')
                ->leftJoin('p.personnagesReligions', 'pr')
                ->leftJoin('pr.religion', 'r')
                ->leftJoin('pr.religionLevel', 'rl');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Personnages inscrits au GN (participant avec billet) parmi ceux donnés.
     *
     * @param list<int> $personnageIds
     *
     * @return array<int, ?string> id du personnage => nom du groupe de jeu (null si aucun)
     */
    public function findInscriptions(Gn $gn, array $personnageIds): array
    {
        if ([] === $personnageIds) {
            return [];
        }

        $rows = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('IDENTITY(pa.personnage) AS pid', 'g.nom AS groupe')
            ->from(Participant::class, 'pa')
            ->leftJoin('pa.groupeGn', 'gg')
            ->leftJoin('gg.groupe', 'g')
            ->where('pa.gn = :gn')
            ->andWhere('pa.billet IS NOT NULL')
            ->andWhere('pa.personnage IN (:ids)')
            ->setParameter('gn', $gn->getId())
            ->setParameter('ids', $personnageIds)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['pid']] = $row['groupe'];
        }

        return $result;
    }

    /**
     * @return list<IntrigueHasGroupeSecondaire>
     */
    public function findIntrigues(SecondaryGroup $secondaryGroup): array
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('ih', 'i')
            ->from(IntrigueHasGroupeSecondaire::class, 'ih')
            ->join('ih.intrigue', 'i')
            ->where('ih.secondaryGroup = :g')
            ->orderBy('i.titre', 'ASC')
            ->setParameter('g', $secondaryGroup->getId())
            ->getQuery()
            ->getResult();
    }
}
