<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BilletSync;
use App\Entity\Gn;
use App\Enum\SyncConfiance;
use App\Enum\SyncEtat;
use Doctrine\ORM\Tools\Pagination\Paginator;

class BilletSyncRepository extends BaseRepository
{
    public function findOneByAttendee(Gn $gn, string $attendeeId): ?BilletSync
    {
        $sync = $this->findOneBy(['gn' => $gn, 'attendeeId' => $attendeeId]);

        return $sync instanceof BilletSync ? $sync : null;
    }

    /**
     * Lignes d'un GN filtrées par état, confiance, statut HelloAsso, produit et recherche (nom ou e-mail), paginées.
     *
     * @return Paginator<BilletSync>
     */
    public function paginate(Gn $gn, ?SyncEtat $etat, ?SyncConfiance $confiance, ?string $status, ?string $productId, ?string $search, int $limit, int $page): Paginator
    {
        $qb = $this
            ->createQueryBuilder('s')
            ->andWhere('s.gn = :gn')
            ->setParameter('gn', $gn)
            ->orderBy('s.email', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit);

        if (null !== $etat) {
            $qb->andWhere('s.etat = :etat')->setParameter('etat', $etat);
        }
        if (null !== $confiance) {
            $qb->andWhere('s.confiance = :confiance')->setParameter('confiance', $confiance);
        }
        if (null !== $status && '' !== $status) {
            $qb->andWhere('s.status = :status')->setParameter('status', $status);
        }
        if (null !== $productId && '' !== $productId) {
            $qb->andWhere('s.productId = :productId')->setParameter('productId', $productId);
        }
        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('s.email LIKE :search OR s.nom LIKE :search')->setParameter('search', '%' . trim($search) . '%');
        }

        return new Paginator($qb->getQuery());
    }

    /**
     * Nombre de lignes d'un GN par valeur d'une colonne (etat, confiance, status ou productId), éventuellement dans un état.
     *
     * @return array<string, int>
     */
    public function countBy(Gn $gn, string $column, ?SyncEtat $etat = null): array
    {
        if (!\in_array($column, ['etat', 'confiance', 'status', 'productId'], true)) {
            throw new \InvalidArgumentException('Colonne de comptage inconnue : ' . $column);
        }

        $qb = $this
            ->createQueryBuilder('s')
            ->select('s.' . $column . ' AS valeur', 'COUNT(s.id) AS nb')
            ->andWhere('s.gn = :gn')
            ->setParameter('gn', $gn)
            ->groupBy('s.' . $column);
        if (null !== $etat) {
            $qb->andWhere('s.etat = :etat')->setParameter('etat', $etat);
        }

        $counts = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $value = $row['valeur'];
            $counts[$value instanceof \BackedEnum ? (string) $value->value : (string) $value] = (int) $row['nb'];
        }

        return $counts;
    }

    /**
     * Couples produit + prix présents dans les participants du GN, avec leur effectif.
     *
     * @return list<array{product: string, price: int|null, nb: int}>
     */
    public function countTarifs(Gn $gn): array
    {
        $rows = $this
            ->createQueryBuilder('s')
            ->select('s.productId AS product', 's.price AS price', 'COUNT(s.id) AS nb')
            ->andWhere('s.gn = :gn')
            ->andWhere('s.productId IS NOT NULL')
            ->setParameter('gn', $gn)
            ->groupBy('s.productId', 's.price')
            ->orderBy('s.productId', 'ASC')
            ->addOrderBy('s.price', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'product' => (string) $row['product'],
            'price' => null === $row['price'] ? null : (int) $row['price'],
            'nb' => (int) $row['nb'],
        ], $rows);
    }

    /** @return list<BilletSync> */
    public function findByGnAndEtat(Gn $gn, ?SyncEtat $etat = null): array
    {
        $criteria = ['gn' => $gn];
        if (null !== $etat) {
            $criteria['etat'] = $etat;
        }

        $rows = $this->findBy($criteria, ['email' => 'ASC']);

        return array_values(array_filter($rows, static fn (object $row): bool => $row instanceof BilletSync));
    }
}
