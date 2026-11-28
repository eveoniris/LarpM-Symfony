<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Reçoit les rapports de violation de la Content-Security-Policy envoyés par les navigateurs
 * (directive report-uri) et les écrit dans le canal de log « csp » (var/log/csp.log en prod).
 *
 * Route publique : les navigateurs l'appellent aussi depuis les pages vues sans être connecté.
 * Limitée par IP et en taille pour qu'elle ne serve pas à remplir les logs.
 */
#[AsController]
final class CspRapportController
{
    public const string CHEMIN = '/csp/rapport';

    private const int TAILLE_MAX = 8192;

    #[Route(self::CHEMIN, name: 'csp.rapport', methods: ['POST'])]
    public function __invoke(Request $request, LoggerInterface $cspLogger, RateLimiterFactoryInterface $cspRapportIpLimiter): Response
    {
        if (!$cspRapportIpLimiter->create((string) $request->getClientIp())->consume()->isAccepted()) {
            return new Response(status: Response::HTTP_TOO_MANY_REQUESTS);
        }

        $contenu = $request->getContent();
        if (\strlen($contenu) > self::TAILLE_MAX) {
            return new Response(status: Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        /** @var mixed $donnees */
        $donnees = json_decode($contenu, associative: true);
        $rapport = \is_array($donnees) && \is_array($donnees['csp-report'] ?? null) ? $donnees['csp-report'] : null;
        if (null === $rapport) {
            return new Response(status: Response::HTTP_BAD_REQUEST);
        }

        $cspLogger->warning('Violation CSP', [
            'page' => self::texte($rapport['document-uri'] ?? null),
            'directive' => self::texte($rapport['violated-directive'] ?? $rapport['effective-directive'] ?? null),
            'bloque' => self::texte($rapport['blocked-uri'] ?? null),
            'source' => self::texte($rapport['source-file'] ?? null),
            'ligne' => \is_int($rapport['line-number'] ?? null) ? $rapport['line-number'] : null,
        ]);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private static function texte(mixed $valeur): ?string
    {
        return \is_string($valeur) ? mb_substr($valeur, start: 0, length: 300) : null;
    }
}
