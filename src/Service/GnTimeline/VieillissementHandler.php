<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Service\PersonnageService;

/**
 * Vieillit tous les personnages (global). Le garde-fou de PersonnageService::vieillirTous évite un second passage pour la même
 * date de jeu ; l'étape reste manuelle.
 */
final readonly class VieillissementHandler implements GnTimelineHandlerInterface
{
    public function __construct(
        private PersonnageService $personnageService,
    ) {
    }

    public function type(): GnTimelineType
    {
        return GnTimelineType::VIEILLISSEMENT;
    }

    public function executer(GnTimelineStep $etape): string
    {
        $traites = 0;
        foreach ($this->personnageService->vieillirTous() as $_) {
            ++$traites;
        }

        return \sprintf('Vieillissement exécuté (%d éléments traités).', $traites);
    }
}
