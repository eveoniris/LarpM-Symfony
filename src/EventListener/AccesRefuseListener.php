<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\LogAction;
use App\Entity\User;
use App\Enum\LogActionType;
use App\Security\AccesRefuseJournal;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

/**
 * Trace les refus d'accès d'un utilisateur connecté.
 *
 * Fichier (var/log/error_acces.log) : tous les refus. Base (log_action no_access) : seulement les refus sur
 * une URL portant un identifiant numérique (tentative d'accès à une ressource par incrément d'id), hors
 * jeton CSRF. Priorité supérieure à celle de AccessDeniedListener, qui stoppe la propagation.
 */
final readonly class AccesRefuseListener implements EventSubscriberInterface
{
    public function __construct(
        private AccesRefuseJournal $journal,
        private Security $security,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->getThrowable() instanceof AccessDeniedException) {
            return;
        }

        $request = $event->getRequest();
        $user = $this->security->getUser();
        if (!$user instanceof User || str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        try {
            $this->tracer($request, $user, $event->getThrowable());
        } catch (Throwable $e) {
            // La trace ne doit jamais empêcher la réponse de refus.
            $this->logger->warning('Trace du refus d\'accès impossible : ' . $e->getMessage());
        }
    }

    private function tracer(Request $request, User $user, Throwable $exception): void
    {
        $route = (string) $request->attributes->get('_route', '');
        $ligne = [
            'user_id' => $user->getId(),
            'route' => $route,
            'url' => $request->getRequestUri(),
            'methode' => $request->getMethod(),
            'ip' => $request->getClientIp(),
            'message' => $exception->getMessage(),
        ];
        $this->journal->ecrire($ligne);

        if (!$this->estTentativeParIdentifiant($request, $exception)) {
            return;
        }

        $log = new LogAction();
        $log->setDate(new DateTime());
        $log->setUser($user);
        $log->setType(LogActionType::NO_ACCESS);
        $log->setData(['data' => ['route' => $route, 'url' => $ligne['url'], 'message' => $ligne['message']]]);
        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function estTentativeParIdentifiant(Request $request, Throwable $exception): bool
    {
        if (str_contains(strtolower($exception->getMessage()), 'csrf')) {
            return false;
        }

        $params = $request->attributes->get('_route_params', []);
        foreach (\is_array($params) ? $params : [] as $valeur) {
            if (\is_scalar($valeur) && ctype_digit((string) $valeur)) {
                return true;
            }
        }

        return false;
    }
}
