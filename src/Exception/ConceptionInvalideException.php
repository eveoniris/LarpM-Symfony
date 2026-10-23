<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

/**
 * L'enregistrement d'une conception est refusé (enveloppe de groupe, saisie terminée, partenaire introuvable, couple déjà signalé).
 * Le message est destiné à être affiché tel quel au dépouilleur.
 */
final class ConceptionInvalideException extends RuntimeException
{
}
