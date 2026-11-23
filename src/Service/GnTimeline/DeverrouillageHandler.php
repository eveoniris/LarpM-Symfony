<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Service\GnLockService;
use LogicException;

/**
 * Déverrouille tous les groupes du GN, niveaux souple et strict.
 */
final readonly class DeverrouillageHandler implements GnTimelineHandlerInterface
{
    public function __construct(
        private GnLockService $gnLockService,
    ) {
    }

    public function type(): GnTimelineType
    {
        return GnTimelineType::DEVERROUILLAGE;
    }

    public function executer(GnTimelineStep $etape): string
    {
        $gn = $etape->getGn() ?? throw new LogicException('Étape sans GN.');
        $this->gnLockService->deverrouiller($gn);

        return \sprintf('Groupes de %s déverrouillés.', $gn->getLabel());
    }
}
