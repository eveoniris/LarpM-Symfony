<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\LogActionType;
use App\Service\OrderBy;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

class LogActionRepository extends BaseRepository
{
    /** @param string|array<int|string, string|array<string, mixed>|null>|null $attributes */
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias . '.user', 'user');
        $query->leftJoin('user.etatCivil', 'etatCivil');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    /** @return array<int, string> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            'user.username as username',
            $alias . '.date',
            $alias . '.data',
            'etatCivil.nom as nom',
            'etatCivil.prenom as prenom',
            "CONCAT(etatCivil.nom, ' ', etatCivil.prenom) AS HIDDEN nomPrenom",
        ];
    }

    public function getLastAgingActionDate(): ?int
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('data', 'data', 'string');

        /* @noinspection SqlNoDataSourceInspection */
        $data = $this->entityManager
            ->createNativeQuery(<<<SQL
                SELECT data
                FROM log_action la
                WHERE la.type = :type
                ORDER BY id DESC
                LIMIT 1
                SQL, $rsm)
            ->setParameter('type', LogActionType::AGING_CHARACTERS->value)
            ->getOneOrNullResult();

        if (empty($data['data'])) {
            return null;
        }

        return json_decode($data['data'], true, 512, \JSON_THROW_ON_ERROR)['gn_date'] ?? null;
    }
}
