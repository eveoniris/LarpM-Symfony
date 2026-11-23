<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Service\GnLockService;
use LogicException;

/**
 * Verrouillage strict des groupes du GN : seuls les admins peuvent ensuite modifier. Aucun effet de compétence.
 */
final readonly class VerrouillageStrictHandler implements GnTimelineHandlerInterface
{
    public function __construct(
        private GnLockService $gnLockService,
    ) {
    }

    public function type(): GnTimelineType
    {
        return GnTimelineType::VERROUILLAGE_STRICT;
    }

    public function executer(GnTimelineStep $etape): string
    {
        $gn = $etape->getGn() ?? throw new LogicException('Étape sans GN.');
        $this->gnLockService->verrouillerStrict($gn);

        return \sprintf('Groupes de %s verrouillés strictement (admins seuls).', $gn->getLabel());
    }
}
