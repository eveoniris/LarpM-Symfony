<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\LogActionType;
use App\Service\OrderBy;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
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
        // leftJoin : les logs sans utilisateur (commande, cron) doivent rester visibles
        $query->leftJoin($alias . '.user', 'user');
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
            $alias . '.type',
            $alias . '.ip',
            'etatCivil.nom as nom',
            'etatCivil.prenom as prenom',
            "CONCAT(etatCivil.nom, ' ', etatCivil.prenom) AS HIDDEN nomPrenom",
        ];
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        return parent::translateAttributes()
        + [
            'username' => 'Utilisateur',
            'date' => 'Date',
            'data' => 'Données',
            'type' => 'Type d\'action (valeur technique)',
            'ip' => 'Adresse IP',
            'nom' => 'Nom',
            'prenom' => 'Prénom',
        ];
    }

    /** @return array<string, mixed> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        $sorts = [];
        foreach (['id' => $alias . '.id', 'date' => $alias . '.date', 'type' => $alias . '.type', 'username' => 'user.username'] as $name => $field) {
            $sorts[$name] = [
                OrderBy::ASC => [$field => OrderBy::ASC],
                OrderBy::DESC => [$field => OrderBy::DESC],
            ];
        }

        return $sorts;
    }

    /**
     * Valeurs de type réellement présentes en base (y compris hors enum).
     *
     * @return array<int, string>
     */
    public function findDistinctTypes(): array
    {
        return array_map(
            static fn (array $row): string => (string) $row['type'],
            $this->createQueryBuilder('la')->select('DISTINCT la.type AS type')->orderBy('la.type')->getQuery()->getScalarResult(),
        );
    }

    /**
     * Nombre de logs antérieurs à la date, hors types protégés (jamais purgés).
     *
     * @param list<string> $protectedTypes valeurs de LogActionType à conserver
     */
    public function countOlderThan(DateTimeImmutable $before, array $protectedTypes): int
    {
        $queryBuilder = $this
            ->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->where('la.date < :before')
            ->setParameter('before', $before);

        if ([] !== $protectedTypes) {
            $queryBuilder->andWhere('la.type NOT IN (:protected)')->setParameter('protected', $protectedTypes);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Supprime au plus $limit logs antérieurs à la date, hors types protégés.
     *
     * SQL natif volontaire : contourne les écouteurs Doctrine (pas de log de la suppression de logs)
     * et le LIMIT garde chaque transaction courte sur une table volumineuse.
     *
     * @param list<string> $protectedTypes valeurs de LogActionType à conserver
     */
    public function deleteBatchOlderThan(DateTimeImmutable $before, array $protectedTypes, int $limit): int
    {
        $sql = 'DELETE FROM log_action WHERE date < :before';
        $params = ['before' => $before->format('Y-m-d H:i:s'), 'limit' => $limit];
        $types = ['before' => ParameterType::STRING, 'limit' => ParameterType::INTEGER];

        if ([] !== $protectedTypes) {
            $sql .= ' AND type NOT IN (:protected)';
            $params['protected'] = $protectedTypes;
            $types['protected'] = ArrayParameterType::STRING;
        }

        return (int) $this
            ->getEntityManager()
            ->getConnection()
            ->executeStatement($sql . ' LIMIT :limit', $params, $types);
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
