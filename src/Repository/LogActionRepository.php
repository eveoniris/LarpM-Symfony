<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\LogActionType;
use App\Security\IpAnonymizer;
use App\Service\OrderBy;
use DateTimeImmutable;
use Generator;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

// @mago-expect lint:too-many-methods
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

    /** Nombre d'IP qui seraient modifiées par l'anonymisation des logs antérieurs à la date. */
    public function countIpsToAnonymize(DateTimeImmutable $before): int
    {
        return iterator_count($this->ipsToAnonymize($before));
    }

    /**
     * Anonymise les IP des logs antérieurs à la date (voir IpAnonymizer) et renvoie le nombre de lignes modifiées.
     *
     * Aucune colonne « déjà anonymisée » : on ne réécrit que les lignes dont l'IP change, donc la commande est
     * idempotente. SQL natif volontaire : contourne les écouteurs Doctrine.
     */
    public function anonymizeIpsOlderThan(DateTimeImmutable $before): int
    {
        $connection = $this->getEntityManager()->getConnection();
        $changed = 0;

        foreach ($this->ipsToAnonymize($before) as $id => $anonymized) {
            $connection->executeStatement('UPDATE log_action SET ip = :ip WHERE id = :id', ['ip' => $anonymized, 'id' => $id]);
            ++$changed;
        }

        return $changed;
    }

    /**
     * Parcourt les logs anciens par id croissant, par lots (curseur sur l'id), et produit id => IP anonymisée
     * pour ceux dont l'IP doit changer (null si l'IP est invalide).
     *
     * @return Generator<int, string|null>
     */
    private function ipsToAnonymize(DateTimeImmutable $before, int $batchSize = 5000): Generator
    {
        $connection = $this->getEntityManager()->getConnection();
        $select = 'SELECT id, ip FROM log_action WHERE date < :before AND ip IS NOT NULL AND id > :last ORDER BY id LIMIT :limit';
        $lastId = 0;

        do {
            /** @var list<array{id: int|string, ip: string}> $rows */
            $rows = $connection->fetchAllAssociative(
                $select,
                ['before' => $before->format('Y-m-d H:i:s'), 'last' => $lastId, 'limit' => $batchSize],
                ['before' => ParameterType::STRING, 'last' => ParameterType::INTEGER, 'limit' => ParameterType::INTEGER],
            );

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $anonymized = IpAnonymizer::anonymize($row['ip']);
                if ($anonymized !== $row['ip']) {
                    yield $lastId => $anonymized;
                }
            }
        } while (\count($rows) === $batchSize);
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
