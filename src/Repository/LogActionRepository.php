<?php

namespace App\Repository;

use App\Entity\LogAction;
use App\Service\OrderBy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAction>
 */
class LogActionRepository extends BaseRepository
{
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
        $query->leftJoin('user.etatCivil', 'etatCivil');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias = static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            'user.username as username',
            $alias.'.date',
            $alias.'.data',
            'etatCivil.nom as nom',
            'etatCivil.prenom as prenom',
            "CONCAT(etatCivil.nom, ' ', etatCivil.prenom) AS HIDDEN nomPrenom",

        ];
    }
}
