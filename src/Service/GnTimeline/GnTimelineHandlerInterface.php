<?php

declare(strict_types=1);

namespace App\Service\GnTimeline;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Action de jeu associée à un type d'étape de la chronologie.
 */
#[AutoconfigureTag('app.gn_timeline_handler')]
interface GnTimelineHandlerInterface
{
    public function type(): GnTimelineType;

    /**
     * Exécute l'action et renvoie un compte rendu court ; lève une exception en cas d'échec.
     */
    public function executer(GnTimelineStep $etape): string;
}
