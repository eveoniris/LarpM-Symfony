<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * La validation ou le renvoi d'un dépouillement est refusé (mauvais statut, motif manquant).
 * Le message est destiné à être affiché tel quel.
 */
final class ValidationInvalideException extends RuntimeException
{
}
