<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BilletTarif;
use App\Entity\Gn;

class BilletTarifRepository extends BaseRepository
{
    /** @return list<BilletTarif> */
    public function findByGn(Gn $gn): array
    {
        return array_values(array_filter($this->findBy(['gn' => $gn]), static fn (object $row): bool => $row instanceof BilletTarif));
    }

    /**
     * Tarif applicable à un produit payé à un prix donné : correspondance exacte, sinon tarif valable à tout prix.
     *
     * @param list<BilletTarif> $tarifs tarifs du GN (évite une requête par ligne)
     */
    public static function resolve(array $tarifs, ?string $productId, ?int $price): ?BilletTarif
    {
        if (null === $productId) {
            return null;
        }

        $generic = null;
        foreach ($tarifs as $tarif) {
            if ($tarif->getProductId() !== $productId) {
                continue;
            }
            if (null !== $price && $tarif->getPrice() === $price) {
                return $tarif;
            }
            if (null === $tarif->getPrice()) {
                $generic = $tarif;
            }
        }

        return $generic;
    }
}
