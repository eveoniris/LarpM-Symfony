<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Controller\CspRapportController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * En-têtes de sécurité ajoutés à chaque réponse, sans écraser ceux posés par un contrôleur.
 *
 * La Content-Security-Policy est envoyée en mode rapport (Content-Security-Policy-Report-Only) tant que
 * CSP_ENFORCE vaut 0 : le navigateur ne bloque rien et signale les violations à CspRapportController.
 * Les templates contiennent encore des <script> et des attributs on* inline, d'où 'unsafe-inline' :
 * la politique bloque déjà les scripts d'origines inconnues, les objets, base-uri, les envois de
 * formulaire hors du site et l'affichage du site dans un cadre étranger.
 */
final class EnTetesSecuriteSubscriber implements EventSubscriberInterface
{
    private const array EN_TETES = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /** CDN encore utilisés par les templates (datatables, select2, chart.js...). */
    private const string CDN = 'https://cdn.datatables.net https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.rawgit.com';

    public const string CSP =
        "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' "
            . self::CDN
            . '; '
            . "style-src 'self' 'unsafe-inline' "
            . self::CDN
            . '; '
            . "font-src 'self' data: "
            . self::CDN
            . '; '
            . "img-src 'self' data: blob: https:; "
            . "connect-src 'self'; "
            . "worker-src 'self'; "
            . "frame-src 'self' https://www.helloasso.com https://www.youtube.com https://www.youtube-nocookie.com; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'self'; "
            . 'report-uri '
            . CspRapportController::CHEMIN;

    public function __construct(
        #[Autowire(env: 'bool:CSP_ENFORCE')]
        private readonly bool $cspAppliquee = false,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onResponse'];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;
        $enTetes = self::EN_TETES;
        $enTetes[$this->cspAppliquee ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only'] = self::CSP;

        foreach ($enTetes as $nom => $valeur) {
            if ($headers->has($nom)) {
                continue;
            }

            $headers->set($nom, $valeur);
        }

        // HSTS uniquement en HTTPS : en HTTP (Docker local) le navigateur l'ignore de toute façon.
        if ($event->getRequest()->isSecure() && !$headers->has('Strict-Transport-Security')) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }
    }
}
