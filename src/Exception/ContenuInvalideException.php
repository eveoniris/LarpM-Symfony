<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * Le contenu saisi pour une enveloppe est refusé (dépouillement verrouillé, mauvaise nature d'enveloppe).
 * Le message est destiné à être affiché tel quel au dépouilleur.
 */
final class ContenuInvalideException extends RuntimeException
{
}
