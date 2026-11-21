<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Repository\GnRepository;
use LogicException;

/**
 * Donne l'XP de participation (idempotent : un personnage ne reçoit pas deux fois le même gain pour un GN).
 */
final readonly class XpParticipationHandler implements GnTimelineHandlerInterface
{
    public function __construct(
        private GnRepository $gnRepository,
    ) {
    }

    public function type(): GnTimelineType
    {
        return GnTimelineType::XP_PARTICIPATION;
    }

    public function executer(GnTimelineStep $etape): string
    {
        $gn = $etape->getGn() ?? throw new LogicException('Étape sans GN.');
        $xp = $etape->getXp();
        if (null === $xp || $xp <= 0) {
            throw new LogicException("Le nombre d'XP à donner est obligatoire (supérieur à 0).");
        }

        $result = $this->gnRepository->giveParticipationXp($gn, $xp);

        return \sprintf('%d XP de participation donnés pour %s (%d gains enregistrés).', $xp, $gn->getLabel(), $result->rowCount());
    }
}
