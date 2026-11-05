<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\LogAction;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Renseigne l'IP et le user agent de la requête HTTP courante sur chaque nouveau log_action.
 *
 * Point d'écriture unique : couvre les contrôleurs, les services et le listener de suppression.
 * Hors requête HTTP (commande console, cron) les champs restent null.
 */
#[AsEntityListener(event: Events::prePersist, entity: LogAction::class)]
final readonly class LogActionRequestListener
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function prePersist(LogAction $log, PrePersistEventArgs $args): void
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        if (null === $log->getIp()) {
            $log->setIp($request->getClientIp());
        }

        if (null === $log->getUserAgent()) {
            $log->setUserAgent($request->headers->get('User-Agent'));
        }
    }
}
