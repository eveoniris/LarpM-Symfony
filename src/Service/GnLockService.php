<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Gn;
use App\Repository\GnRepository;

/**
 * Verrouillage de l'édition des groupes d'un GN et effets de fin d'édition.
 *
 * Même chaîne que le bouton « Verrouiller » de la fiche GN ; partagée avec l'exécution planifiée.
 */
final readonly class GnLockService
{
    public function __construct(
        private GnRepository $gnRepository,
        private PersonnageService $personnageService,
    ) {
    }

    public function verrouiller(Gn $gn): void
    {
        $this->gnRepository->lockAllGroup($gn);

        $this->personnageService->lockGnSetDefaultCharacter($gn);
        $this->personnageService->lockGnSetDefaultLangue($gn);
        $this->personnageService->lockGnSetDefaultSecondCharacter($gn);

        // Ces méthodes sont des générateurs : sans itération leur corps ne s'exécute pas
        foreach ($this->personnageService->lockGnGiveNoblityGnRenomme($gn) as $_) {
        }
        foreach ($this->personnageService->lockGnGiveLiteratureGnBonus($gn) as $_) {
        }
        foreach ($this->personnageService->lockGnGiveSanctuaireGnEffect() as $_) {
        }
    }
}
