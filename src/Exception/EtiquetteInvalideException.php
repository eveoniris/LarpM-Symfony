<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * La saisie d'une étiquette est refusée (champ manquant, qualité mal formée, dépouillement verrouillé).
 * Le message est destiné à être affiché tel quel au dépouilleur.
 */
final class EtiquetteInvalideException extends RuntimeException
{
}
