<?php


namespace App\Repository;

use App\Service\OrderBy;
use App\Service\PagerService;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * LarpManager\Repository\ParticipantRepository.
 *
 * @author kevin
 */
class ParticipantRepository extends BaseRepository
{
    /**
     * Trouve le nombre d'utilisateurs correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('p'));
        $qb->from(\App\Entity\Participant::class, 'p');
        $qb->join('p.gn', 'gn');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function searchPaginatedByGn(PagerService $pageRequest, int $gnid): Paginator
    {
        $query = $this->searchByGn(
            $gnid,
            $pageRequest->getSearchValue(),
            $pageRequest->getSearchType(),
            $pageRequest->getOrderBy(),
            $this->getAlias(),            
        )->getQuery();

        return $this->findPaginatedQuery(
            $query,
            $pageRequest->getLimit(),
            $pageRequest->getPage()
        );
    }

    public function searchByGn(
        int $gnid,
        mixed $search = null,
        null|string|array $attributes = self::SEARCH_NOONE,
        OrderBy $orderBy = null,
        string $alias = null,
        QueryBuilder $query = null        
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);

        $query->andWhere($alias.'.personnage is not null');
        $query->andWhere($alias.'.gn = :value');
        $query->setParameter('value', $gnid);

        $query->join($alias.'.groupeGn', 'groupeGn');
        $query->join('groupeGn.groupe', 'groupe');
        //$query->leftJoin($alias.'.player', 'player');
        //$query->join($alias.'.user', 'user');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    /*public function sortAttributes(string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        dump(1);

        return [
            //'groupe' => [OrderBy::ASC => ['groupe' => OrderBy::ASC], OrderBy::DESC => ['groupe' => OrderBy::DESC]],
            ...parent::sortAttributes($alias),
            // using alias like "scriptwriter" require another template loop due to the added $query->select() entity
            //'user.username' => [OrderBy::ASC => ['user.username' => OrderBy::ASC], OrderBy::DESC => ['user.username' => OrderBy::DESC]],
            //'player.username' => [OrderBy::ASC => ['player.username' => OrderBy::ASC], OrderBy::DESC => ['player.username' => OrderBy::DESC]],
        ];
    }*/
}
