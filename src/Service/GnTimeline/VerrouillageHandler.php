<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Service\GnLockService;
use LogicException;

/**
 * Verrouille les groupes du GN et applique les effets associés.
 */
final readonly class VerrouillageHandler implements GnTimelineHandlerInterface
{
    public function __construct(
        private GnLockService $gnLockService,
    ) {
    }

    public function type(): GnTimelineType
    {
        return GnTimelineType::VERROUILLAGE;
    }

    public function executer(GnTimelineStep $etape): string
    {
        $gn = $etape->getGn() ?? throw new LogicException('Étape sans GN.');
        $this->gnLockService->verrouiller($gn);

        return \sprintf('Groupes de %s verrouillés, effets de fin d\'édition appliqués.', $gn->getLabel());
    }
}
