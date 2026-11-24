<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * En-têtes de sécurité ajoutés à chaque réponse, sans écraser ceux posés par un contrôleur.
 *
 * Pas de Content-Security-Policy pour l'instant : les templates contiennent encore des <script>
 * et des attributs on* inline, une CSP stricte les casserait.
 */
final class EnTetesSecuriteSubscriber implements EventSubscriberInterface
{
    private const array EN_TETES = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

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
        foreach (self::EN_TETES as $nom => $valeur) {
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
