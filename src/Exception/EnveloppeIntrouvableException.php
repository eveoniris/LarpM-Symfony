<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Le numéro saisi par le dépouilleur ne correspond à aucun groupe ou personnage du GN.
 * Le message est destiné à être affiché tel quel au dépouilleur.
 */
final class EnveloppeIntrouvableException extends RuntimeException
{
}
