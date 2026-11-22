<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Service\PersonnageService;

/**
 * Vieillit tous les personnages (global), avec les années et l'âge de mort de l'étape. Le garde-fou de
 * PersonnageService::vieillirTous évite un second passage pour la même date de jeu.
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
        $annees = $etape->getAnnees() ?? GnTimelineStep::ANNEES_DEFAUT;
        $ageMort = $etape->getAgeMort() ?? GnTimelineStep::AGE_MORT_DEFAUT;

        foreach ($this->personnageService->vieillirTous($annees, $ageMort) as $_) {
            ++$traites;
        }

        return \sprintf('Vieillissement exécuté : +%d ans, mort de vieillesse possible dès %d ans (%d éléments traités).', $annees, $ageMort, $traites);
    }
}
