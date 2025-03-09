<?php

namespace App\Repository;

use App\Entity\Membre;
use App\Entity\Personnage;
use App\Entity\SecondaryGroup;
use App\Entity\User;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Deprecated;

class SecondaryGroupRepository extends BaseRepository
{
    /**
     * Trouve tous les groupes secondaire publics.
     */
    public function findAllPublic()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\SecondaryGroup g WHERE g.secret = false or g.secret is null')
            ->getResult();
    }

    /**
     * Trouve les groupes secondaires correspondants aux critères de recherche.
     *
     * @param unknown $limit
     * @param unknown $offset
     */
    #[Deprecated]
    public function findList($limit, $offset, array $criteria = [], array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct g');
        $qb->from(SecondaryGroup::class, 'g');

        foreach ($criteria as $critere) {
            $qb->andWhere($critere);
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('g.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    /**
     * Compte les groupes secondaires correspondants aux critères de recherche.
     */
    #[Deprecated]
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('g'));
        $qb->from(SecondaryGroup::class, 'g');

        foreach ($criteria as $critere) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $critere);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $orderBy ??= $this->orderBy;

        if ('secret' === $attributes) {
            $query ??= $this->createQueryBuilder($alias)
                ->orderBy($orderBy->getSort(), $orderBy->getOrderBy());

            return $this->secret(
                $query,
                filter_var($search, FILTER_VALIDATE_BOOLEAN)
            );
        }

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function secret(QueryBuilder $query, bool $secret): QueryBuilder
    {
        if (!$secret) {
            $query->andWhere($this->alias.'.secret = :value OR '.$this->alias.'.secret IS NULL');
        } else {
            $query->andWhere($this->alias.'.secret = :value');
        }

        return $query->setParameter('value', $secret);
    }

    public function visibleForUser(User $user): QueryBuilder
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT DISTINCT sg
                FROM App\Entity\User u 
                INNER JOIN App\Entity\Personnage p
                LEFT JOIN App\Entity\SecondaryGroup sg
                LEFT JOIN App\Entity\membre m
                WHERE u.id = :uid AND (sg.id IS NOT NULL OR m.id IS NOT NULL)
                DQL
            );

        return $query->setParameter('uid', $user->getId())->getScalarResult();
    }

    public function userIsGroupLeader(User $user, SecondaryGroup $secondaryGroup): bool
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT MAX(sg) as exists
                FROM App\Entity\User u 
                INNER JOIN App\Entity\Personnage p
                INNER JOIN App\Entity\SecondaryGroup sg
                WHERE u.id = :uid AND sg.id = :sgid
                DQL
            );

        return (bool) $query
            ->setParameter('uid', $user->getId())
            ->setParameter('sgid', $secondaryGroup->getId())
            ->getSingleScalarResult();

    }

    public function isMember(SecondaryGroup $secondaryGroup, ?Membre $membre = null, ?Personnage $personnage = null): bool
    {
        $query = $this->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT 1 FROM App\Entity\membre m 
                WHERE (m.personnage.id = :pid OR id = :m.id) AND secondary_groupe_id = :sgid
                DQL
            );

        return $query
            ->setParameter('pid', $personnage?->getId())
            ->setParameter('mid', $membre?->getId())
            ->setParameter('sgid', $secondaryGroup->getId())
            ->getScalarResult();
    }

    public function visibleForPersonnage(QueryBuilder $queryBuilder, array $personnagesIds): QueryBuilder
    {
        return $queryBuilder->andWhere($this->alias.'.personnage_id IN (:personnagesIds)')
                ->setParameter('personnagesIds', $personnagesIds);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias.'.label', // => 'Libellé',
            $alias.'.description', // => 'Description',
            $alias.'.description_secrete',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias.'.label' => [
                OrderBy::ASC => [$alias.'.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label' => OrderBy::DESC],
            ],
            $alias.'.description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            $alias.'.description_secrete' => [
                OrderBy::ASC => [$alias.'.description_secrete' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description_secrete' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description_secrete' => $this->translator->trans('Description secrète', domain: 'repository'),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'label' => $this->translator->trans('Nom', domain: 'repository'),
        ];
    }

    public function getPersonnages(SecondaryGroup $secondaryGroup): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('perso')
            ->innerJoin('perso.secondaryGroups', 'sg')
            ->where('sg.id = :sgid')
            ->setParameter('sgid', $secondaryGroup->getId());
    }
}
