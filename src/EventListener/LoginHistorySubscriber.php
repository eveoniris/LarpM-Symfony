<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Enum\LoginMethod;
use App\Security\LoginFingerprint;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Debug\TraceableAuthenticator;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\JsonLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\RememberMeAuthenticator;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use ReflectionClass;
use Throwable;

use function Symfony\Component\String\u;

/**
 * Enregistre chaque connexion (réussie ou échouée) dans login_history.
 *
 * Seuls les authenticators qui ouvrent réellement une session sont tracés (formulaire web,
 * remember_me, login JSON de l'API) : l'authenticator JWT de l'API déclenche LoginSuccessEvent
 * à chaque requête et ne doit pas être enregistré. Un échec d'écriture ne doit jamais empêcher
 * la connexion : l'erreur est journalisée puis ignorée.
 */
final readonly class LoginHistorySubscriber implements EventSubscriberInterface
{
    /** Authenticators qui ouvrent une session, avec la méthode à enregistrer. */
    private const array TRACKED_AUTHENTICATORS = [
        JsonLoginAuthenticator::class => LoginMethod::API_JWT,
        RememberMeAuthenticator::class => LoginMethod::REMEMBER_ME,
        AbstractLoginFormAuthenticator::class => LoginMethod::WEB,
        FormLoginAuthenticator::class => LoginMethod::WEB,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private LoginFingerprint $fingerprint,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
            SwitchUserEvent::class => 'onSwitchUser',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $method = $this->resolveMethod($event->getAuthenticator());
        if (!$method instanceof LoginMethod) {
            return;
        }

        $user = $event->getUser();
        $entry = $this->createEntry($method, true, $event->getRequest());
        if ($user instanceof User) {
            $entry->setUser($user)->setIdentifier($user->getUserIdentifier());
        }

        $this->save($entry);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $method = $this->resolveMethod($event->getAuthenticator());
        if (!$method instanceof LoginMethod) {
            return;
        }

        $identifier = $event->getPassport()?->getBadge(UserBadge::class)?->getUserIdentifier();
        $entry = $this->createEntry($method, false, $event->getRequest());
        $entry->setIdentifier($identifier)->setFailureReason($this->reasonFrom($event->getException()));

        if (null !== $identifier && '' !== $identifier) {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $identifier]);
            if ($user instanceof User) {
                $entry->setUser($user);
            }
        }

        $this->save($entry);
    }

    /**
     * Impersonation : la ligne est rattachée au compte usurpé, l'identifiant est celui de la personne
     * qui usurpe. La sortie d'impersonation n'est pas enregistrée.
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        $token = $event->getToken();
        if (!$token instanceof SwitchUserToken) {
            return;
        }

        $target = $event->getTargetUser();
        $entry = $this->createEntry(LoginMethod::SWITCH_USER, true, $event->getRequest());
        $entry->setIdentifier($token->getOriginalToken()->getUserIdentifier());
        if ($target instanceof User) {
            $entry->setUser($target);
        }

        $this->save($entry);
    }

    private function resolveMethod(AuthenticatorInterface $authenticator): ?LoginMethod
    {
        if ($authenticator instanceof TraceableAuthenticator) {
            $authenticator = $authenticator->getAuthenticator();
        }

        foreach (self::TRACKED_AUTHENTICATORS as $class => $method) {
            if ($authenticator instanceof $class) {
                return $method;
            }
        }

        return null;
    }

    private function createEntry(LoginMethod $method, bool $success, Request $request): LoginHistory
    {
        return (new LoginHistory($method, $success))
            ->setIp($request->getClientIp())
            ->setUserAgent($request->headers->get('User-Agent'))
            ->setFingerprint($this->fingerprint->compute($request));
    }

    /** BadCredentialsException devient bad_credentials, CustomUserMessageAuthenticationException custom_user_message... */
    private function reasonFrom(Throwable $exception): string
    {
        $short = (new ReflectionClass($exception))->getShortName();

        return u($short)->trimSuffix(['AuthenticationException', 'Exception'])->snake()->toString();
    }

    private function save(LoginHistory $entry): void
    {
        try {
            if (!$this->entityManager->isOpen()) {
                return;
            }

            $this->entityManager->persist($entry);
            $this->entityManager->flush();
        } catch (Throwable $throwable) {
            $this->logger->error('Historique de connexion non enregistré : {message}', [
                'message' => $throwable->getMessage(),
                'exception' => $throwable,
            ]);
        }
    }
}
