<?php

namespace App\Repository;

use App\Entity\LogAction;
use App\Enum\LogActionType;
use App\Service\OrderBy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LogAction>
 */
class LogActionRepository extends BaseRepository
{
    public function search(
        mixed             $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy          $orderBy = null,
        ?string           $alias = null,
        ?QueryBuilder     $query = null,
    ): QueryBuilder
    {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias . '.user', 'user');
        $query->leftJoin('user.etatCivil', 'etatCivil');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias = static::getEntityAlias();

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
        $data = $this->entityManager->createNativeQuery(
            <<<SQL
                SELECT data
                FROM log_action la
                WHERE la.type = :type
                ORDER BY id DESC
                LIMIT 1
                SQL,
            $rsm,
        )
            ->setParameter('type', LogActionType::AGING_CHARACTERS->value)
            ->getOneOrNullResult();

        if (empty($data['data'])) {
            return null;
        }

        return json_decode($data['data'], true, 512, JSON_THROW_ON_ERROR)['gn_date'] ?? null;
    }
}
