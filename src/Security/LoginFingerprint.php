<?php

declare(strict_types=1);

namespace App\Security;

use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Empreinte serveur d'une connexion : HMAC-SHA256 de l'User-Agent et de la langue acceptée.
 *
 * Sert à repérer un « nouvel appareil » dans l'historique de connexion. Ce n'est pas un identifiant
 * d'appareil : deux navigateurs identiques donnent la même empreinte, et une mise à jour du navigateur
 * la change. Le sel (secret applicatif) empêche de la recouper avec une autre source de données.
 * Aucun JavaScript côté client, donc aucun consentement supplémentaire.
 */
final readonly class LoginFingerprint
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        #[SensitiveParameter]
        private string $secret,
    ) {
    }

    /** Null quand la requête ne porte aucun des deux en-têtes (rien à comparer). */
    public function compute(Request $request): ?string
    {
        $userAgent = trim((string) $request->headers->get('User-Agent'));
        $language = mb_strtolower(trim((string) $request->headers->get('Accept-Language')));

        if ('' === $userAgent && '' === $language) {
            return null;
        }

        return hash_hmac('sha256', $userAgent . "\n" . $language, $this->secret);
    }
}
