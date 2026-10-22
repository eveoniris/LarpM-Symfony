<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * La photo envoyée est refusée (type, taille, envoi incomplet, dépouillement verrouillé).
 * Le message est destiné à être affiché tel quel au dépouilleur.
 */
final class PhotoInvalideException extends RuntimeException
{
}
