<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Service\GnTimeline\GnTimelineHandlerInterface;
use RuntimeException;

/**
 * Gestionnaire factice : réussit avec le message « fait » ou lève l'erreur donnée.
 */
final readonly class FauxGnTimelineHandler implements GnTimelineHandlerInterface
{
    public function __construct(
        private GnTimelineType $type,
        private ?string $erreur = null,
    ) {
    }

    public function type(): GnTimelineType
    {
        return $this->type;
    }

    public function executer(GnTimelineStep $etape): string
    {
        if (null !== $this->erreur) {
            throw new RuntimeException($this->erreur);
        }

        return 'fait';
    }
}
