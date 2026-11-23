<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Refus de connexion d'une IP bloquée. Hérite de BadCredentialsException : le message montré au visiteur
 * est celui d'un mauvais identifiant, seul l'historique de connexion garde la raison (blocked_ip).
 */
final class BlockedIpException extends BadCredentialsException
{
}
