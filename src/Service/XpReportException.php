<?php

declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/** Refus d'un report d'XP : le message est destiné à être affiché à l'utilisateur. */
final class XpReportException extends RuntimeException
{
}
