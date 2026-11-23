<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Security\BlockedIpException;
use App\Security\IpBlocklist;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Une IP bloquée ne peut pas ouvrir de session par mot de passe (formulaire web et login JWT) : l'échec est
 * identique à un mauvais mot de passe, même avec les bons identifiants. Les requêtes déjà authentifiées
 * (jeton JWT, remember_me) ne sont pas concernées.
 */
final readonly class BlockedIpLoginListener implements EventSubscriberInterface
{
    public function __construct(
        private IpBlocklist $ipBlocklist,
        private RequestStack $requestStack,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        // Avant le contrôle du mot de passe (priorité 0).
        return [CheckPassportEvent::class => ['onCheckPassport', 512]];
    }

    public function onCheckPassport(CheckPassportEvent $event): void
    {
        if (!$event->getPassport()->hasBadge(PasswordCredentials::class)) {
            return;
        }

        if (!$this->ipBlocklist->isBlocked($this->requestStack->getCurrentRequest()?->getClientIp())) {
            return;
        }

        $this->ipBlocklist->equalizeDelay(80, 200);

        throw new BlockedIpException();
    }
}
