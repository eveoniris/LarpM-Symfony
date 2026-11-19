<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Personnage;

/** Répartition d'une liste de numéros de personnage avant un ajout en masse. */
final readonly class AjoutMasseAnalyse
{
    /**
     * @param list<Personnage> $applicables personnages qui reçoivent l'ajout directement
     * @param list<Personnage> $morts       personnages morts à trancher (XP seulement)
     * @param list<int>        $introuvables
     */
    public function __construct(
        public array $applicables = [],
        public array $morts = [],
        public array $introuvables = [],
    ) {
    }
}
